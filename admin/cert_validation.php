<?php
// Error reporting - turn off HTML errors for AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    error_reporting(0);
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

// Handle AJAX requests for certificate validation & claiming
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $certificate_number = $_POST['certificate_number'] ?? '';
    $certificate_id = $_POST['certificate_id'] ?? 0;
    
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    // Action: Validate certificate by number
    if ($action === 'validate_certificate' && $certificate_number) {
        $query = "SELECT c.*, u.firstname, u.lastname, u.username, u.email 
                  FROM certificates c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.certificate_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $certificate_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($cert = $result->fetch_assoc()) {
            // Check if already claimed
            $claimCheck = $conn->prepare("SELECT id FROM certificate_claims WHERE certificate_id = ?");
            $claimCheck->bind_param("i", $cert['id']);
            $claimCheck->execute();
            $isClaimed = $claimCheck->get_result()->num_rows > 0;
            
            $response = [
                'success' => true,
                'certificate' => [
                    'id' => $cert['id'],
                    'user_id' => $cert['user_id'],
                    'user_name' => $cert['firstname'] . ' ' . $cert['lastname'],
                    'username' => $cert['username'],
                    'email' => $cert['email'],
                    'course_id' => $cert['course_id'],
                    'course_type' => $cert['course_type'],
                    'certificate_number' => $cert['certificate_number'],
                    'final_score' => $cert['final_score'],
                    'total_quizzes_passed' => $cert['total_quizzes_passed'],
                    'total_quizzes' => $cert['total_quizzes'],
                    'download_count' => $cert['download_count'],
                    'issued_at' => $cert['issued_at'],
                    'expiry_date' => $cert['expiry_date'],
                    'is_claimed' => $isClaimed
                ]
            ];
        } else {
            $response = ['success' => false, 'message' => 'Certificate not found in system database.'];
        }
    }
    
    // Action: Claim certificate
    elseif ($action === 'claim_certificate' && $certificate_id) {
        // Check if already claimed
        $checkQuery = "SELECT id FROM certificate_claims WHERE certificate_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("i", $certificate_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $response = ['success' => false, 'message' => 'Certificate already claimed.'];
        } else {
            $admin_id = $_SESSION['user_id'];
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $notes = $_POST['notes'] ?? 'Claimed via validation portal';
            
            $insertQuery = "INSERT INTO certificate_claims (certificate_id, claimed_by_admin_id, claimed_at, ip_address, notes) 
                            VALUES (?, ?, NOW(), ?, ?)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bind_param("iiss", $certificate_id, $admin_id, $ip_address, $notes);
            
            if ($insertStmt->execute()) {
                // Also update download count increment
                $updateDownload = $conn->prepare("UPDATE certificates SET download_count = download_count + 1 WHERE id = ?");
                $updateDownload->bind_param("i", $certificate_id);
                $updateDownload->execute();
                
                $response = ['success' => true, 'message' => 'Certificate claimed successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Database error: ' . $conn->error];
            }
        }
    }
    
    // Action: Get claimed certificates list
    elseif ($action === 'get_claimed_list') {
        $query = "SELECT cc.*, c.certificate_number, c.user_id, c.course_type, c.final_score, c.issued_at,
                         u.firstname, u.lastname, u.username
                  FROM certificate_claims cc
                  JOIN certificates c ON cc.certificate_id = c.id
                  JOIN users u ON c.user_id = u.id
                  ORDER BY cc.claimed_at DESC";
        $result = $conn->query($query);
        $claims = [];
        while ($row = $result->fetch_assoc()) {
            $claims[] = $row;
        }
        $response = ['success' => true, 'claims' => $claims];
    }
    
    echo json_encode($response);
    exit();
}

// Get initial claimed certificates for table display
$claimed_list = [];
$query = "SELECT cc.*, c.certificate_number, c.user_id, c.course_type, c.final_score, c.issued_at,
                 u.firstname, u.lastname, u.username
          FROM certificate_claims cc
          JOIN certificates c ON cc.certificate_id = c.id
          JOIN users u ON c.user_id = u.id
          ORDER BY cc.claimed_at DESC";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $claimed_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Certificate Validation | Admin Panel</title>
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
        
        /* Certificate preview card style */
        .cert-glossy {
            background: linear-gradient(135deg, #fef9e6 0%, #fff6e0 100%);
            border-left: 8px solid #d4af37;
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
        
        /* Table scroll */
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
                <h1 class="text-2xl font-bold text-gray-900">Certificate Validation System</h1>
                <p class="text-sm text-gray-500 mt-1">Verify official certificates and manage claims</p>
            </div>

            <!-- Validation Card -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-5 md:p-6 mb-8 transition-all">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fas fa-shield-alt text-blue-500 text-xl"></i>
                    <h2 class="text-xl font-semibold text-gray-800">Certificate Verification</h2>
                    <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full ml-2">instant validation</span>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Certificate Number / ID</label>
                        <div class="relative">
                            <i class="fas fa-id-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="certificateIdInput" 
                                   placeholder="e.g., CERT-9F3A-2B1D-88E4" 
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Enter the unique certificate identifier from our system database.</p>
                    </div>
                    <button id="validateBtn" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition shadow-sm flex items-center justify-center gap-2 w-full sm:w-auto">
                        <i class="fas fa-check-circle"></i> Validate & Inspect
                    </button>
                </div>
                <div id="validationAlert" class="mt-4 hidden"></div>
            </div>

            <!-- ========== MODAL POPUP ========= -->
            <div id="certModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden modal-transition">
                <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl transform transition-all">
                    <div class="flex justify-between items-center border-b border-gray-100 px-6 py-4">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-certificate text-amber-500 text-2xl"></i>
                            <h3 class="text-xl font-bold text-gray-800">Certificate Preview</h3>
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">verified record</span>
                        </div>
                        <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600 transition text-2xl leading-5">&times;</button>
                    </div>
                    <div class="p-6" id="modalBodyContent">
                        <div class="space-y-4 cert-glossy p-5 rounded-xl border border-amber-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-amber-700 font-semibold">OFFICIAL DOCUMENT</p>
                                    <p class="text-2xl font-bold text-gray-800 mt-1" id="modalCertNumber">—</p>
                                </div>
                                <i class="fas fa-ribbon text-4xl text-amber-400"></i>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div><span class="font-medium text-gray-600">User:</span> <span id="modalUserName" class="text-gray-800">—</span></div>
                                <div><span class="font-medium text-gray-600">Username:</span> <span id="modalUsername" class="text-gray-800">—</span></div>
                                <div><span class="font-medium text-gray-600">Course Type:</span> <span id="modalCourseType" class="text-gray-800 capitalize">—</span></div>
                                <div><span class="font-medium text-gray-600">Final Score:</span> <span id="modalFinalScore" class="text-gray-800">—</span></div>
                                <div><span class="font-medium text-gray-600">Quizzes passed:</span> <span id="modalQuizzesPassed" class="text-gray-800">—</span> / <span id="modalTotalQuizzes">—</span></div>
                                <div><span class="font-medium text-gray-600">Issued at:</span> <span id="modalIssuedAt" class="text-gray-800">—</span></div>
                                <div><span class="font-medium text-gray-600">Expiry date:</span> <span id="modalExpiry" class="text-gray-800">—</span></div>
                                <div><span class="font-medium text-gray-600">Download count:</span> <span id="modalDownloadCount" class="text-gray-800">—</span></div>
                            </div>
                            <div class="border-t border-amber-200 pt-3 mt-1 text-xs text-gray-500 flex justify-between">
                                <span><i class="far fa-clock"></i> blockchain timestamp</span>
                                <span><i class="fas fa-database"></i> system secured</span>
                            </div>
                        </div>
                        <div class="mt-6 flex flex-wrap gap-3 justify-between items-center">
                            <div class="text-sm text-gray-500 bg-gray-50 px-3 py-1.5 rounded-full">
                                <i class="fas fa-fingerprint"></i> ID: <span id="modalRecordId" class="font-mono text-xs">-</span>
                            </div>
                            <button id="claimCertificateBtn" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-7 rounded-xl transition flex items-center gap-2 shadow-md">
                                <i class="fas fa-hand-holding-heart"></i> Claim Certificate
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Claimed Certificates List Table -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex flex-wrap justify-between items-center gap-3">
                    <div>
                        <h2 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                            <i class="fas fa-list-ul text-indigo-500"></i> Claimed Certificates Log
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Records that have been claimed & stored in system ledger</p>
                    </div>
                    <button id="refreshTableBtn" class="text-gray-500 hover:text-indigo-600 text-sm bg-white border rounded-lg px-3 py-1.5 transition flex items-center gap-1">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[1000px] w-full text-sm text-left text-gray-700">
                        <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Cert ID</th>
                                <th class="px-6 py-4">Certificate Number</th>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Course</th>
                                <th class="px-6 py-4">Final Score</th>
                                <th class="px-6 py-4">Claimed At</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody id="claimedTableBody" class="bg-white divide-y divide-gray-100">
                            <?php if (count($claimed_list) > 0): ?>
                                <?php foreach ($claimed_list as $claim): ?>
                                <tr class="hover:bg-indigo-50 transition">
                                    <td class="px-6 py-4 font-medium">#<?php echo $claim['certificate_id']; ?></td>
                                    <td class="px-6 py-4 font-mono text-xs"><?php echo htmlspecialchars($claim['certificate_number']); ?></td>
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="font-medium"><?php echo htmlspecialchars($claim['firstname'] . ' ' . $claim['lastname']); ?></div>
                                            <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($claim['username']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($claim['course_type']); ?></td>
                                    <td class="px-6 py-4"><?php echo $claim['final_score']; ?>%</td>
                                    <td class="px-6 py-4"><?php echo date('M d, Y h:i A', strtotime($claim['claimed_at'])); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">
                                            <i class="fas fa-check-circle mr-1"></i>Claimed
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-gray-400 py-8">No claims recorded yet. Validate a certificate and click "Claim".</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
        });

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast-notification px-4 py-3 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white text-sm flex items-center gap-2`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>${message}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Loading spinner
        function showLoading() {
            let spinner = document.getElementById('globalSpinner');
            if (!spinner) {
                spinner = document.createElement('div');
                spinner.id = 'globalSpinner';
                spinner.className = 'fixed inset-0 bg-black/20 z-50 flex items-center justify-center';
                spinner.innerHTML = '<div class="loading-spinner"></div>';
                document.body.appendChild(spinner);
            }
            spinner.classList.remove('hidden');
        }
        
        function hideLoading() {
            const spinner = document.getElementById('globalSpinner');
            if (spinner) spinner.classList.add('hidden');
        }

        let currentCertificate = null;

        // Validate certificate
        async function validateCertificate(certNumber) {
            showLoading();
            const alertDiv = document.getElementById('validationAlert');
            alertDiv.classList.add('hidden');
            alertDiv.innerHTML = '';
            
            try {
                const formData = new FormData();
                formData.append('action', 'validate_certificate');
                formData.append('certificate_number', certNumber);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentCertificate = data.certificate;
                    showCertificateModal(currentCertificate);
                } else {
                    alertDiv.classList.remove('hidden');
                    alertDiv.innerHTML = `<div class="flex items-center gap-2 bg-red-50 text-red-600 p-3 rounded-lg border border-red-200">
                        <i class="fas fa-times-circle"></i> ${data.message}
                    </div>`;
                }
            } catch (error) {
                console.error('Error:', error);
                alertDiv.classList.remove('hidden');
                alertDiv.innerHTML = `<div class="flex items-center gap-2 bg-red-50 text-red-600 p-3 rounded-lg border border-red-200">
                    <i class="fas fa-exclamation-triangle"></i> Network error. Please try again.
                </div>`;
            } finally {
                hideLoading();
            }
        }

        function showCertificateModal(cert) {
            document.getElementById('modalCertNumber').innerText = cert.certificate_number;
            document.getElementById('modalUserName').innerText = cert.user_name;
            document.getElementById('modalUsername').innerText = '@' + cert.username;
            document.getElementById('modalCourseType').innerText = cert.course_type;
            document.getElementById('modalFinalScore').innerText = `${cert.final_score} / 100`;
            document.getElementById('modalQuizzesPassed').innerText = cert.total_quizzes_passed;
            document.getElementById('modalTotalQuizzes').innerText = cert.total_quizzes;
            document.getElementById('modalIssuedAt').innerText = new Date(cert.issued_at).toLocaleString();
            document.getElementById('modalExpiry').innerText = new Date(cert.expiry_date).toLocaleDateString();
            document.getElementById('modalDownloadCount').innerText = cert.download_count;
            document.getElementById('modalRecordId').innerText = `#${cert.id}`;
            
            const claimBtn = document.getElementById('claimCertificateBtn');
            if (cert.is_claimed) {
                claimBtn.disabled = true;
                claimBtn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
                claimBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
                claimBtn.innerHTML = '<i class="fas fa-check-double"></i> Already Claimed';
            } else {
                claimBtn.disabled = false;
                claimBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                claimBtn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
                claimBtn.innerHTML = '<i class="fas fa-hand-holding-heart"></i> Claim Certificate';
            }
            
            const modal = document.getElementById('certModal');
            modal.classList.remove('hidden');
        }

        async function claimCertificate() {
            if (!currentCertificate || currentCertificate.is_claimed) {
                showToast('Certificate already claimed or invalid', 'error');
                return;
            }
            
            showLoading();
            try {
                const formData = new FormData();
                formData.append('action', 'claim_certificate');
                formData.append('certificate_id', currentCertificate.id);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message, 'success');
                    currentCertificate.is_claimed = true;
                    const claimBtn = document.getElementById('claimCertificateBtn');
                    claimBtn.disabled = true;
                    claimBtn.classList.remove('bg-emerald-600');
                    claimBtn.classList.add('bg-gray-400');
                    claimBtn.innerHTML = '<i class="fas fa-check-circle"></i> Claimed ✓';
                    refreshClaimedTable();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error claiming certificate', 'error');
            } finally {
                hideLoading();
            }
        }

        async function refreshClaimedTable() {
            showLoading();
            try {
                const formData = new FormData();
                formData.append('action', 'get_claimed_list');
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.claims) {
                    const tbody = document.getElementById('claimedTableBody');
                    if (data.claims.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-gray-400 py-8">No claims recorded yet.</td></tr>';
                    } else {
                        tbody.innerHTML = data.claims.map(claim => `
                            <tr class="hover:bg-indigo-50 transition">
                                <td class="px-6 py-4 font-medium">#${claim.certificate_id}</td>
                                <td class="px-6 py-4 font-mono text-xs">${escapeHtml(claim.certificate_number)}</td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium">${escapeHtml(claim.firstname)} ${escapeHtml(claim.lastname)}</div>
                                        <div class="text-xs text-gray-500">@${escapeHtml(claim.username)}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">${escapeHtml(claim.course_type)}</td>
                                <td class="px-6 py-4">${claim.final_score}%</td>
                                <td class="px-6 py-4">${new Date(claim.claimed_at).toLocaleString()}</td>
                                <td class="px-6 py-4"><span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium"><i class="fas fa-check-circle mr-1"></i>Claimed</span></td>
                            </tr>
                        `).join('');
                    }
                    showToast('List refreshed', 'success');
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                hideLoading();
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
        }

        function closeModal() {
            const modal = document.getElementById('certModal');
            modal.classList.add('hidden');
            currentCertificate = null;
        }

        // Event listeners
        document.getElementById('validateBtn').addEventListener('click', () => {
            const input = document.getElementById('certificateIdInput').value.trim();
            if (!input) {
                showToast('Please enter a certificate number', 'error');
                return;
            }
            validateCertificate(input);
        });
        
        document.getElementById('claimCertificateBtn').addEventListener('click', claimCertificate);
        document.getElementById('closeModalBtn').addEventListener('click', closeModal);
        document.getElementById('refreshTableBtn').addEventListener('click', refreshClaimedTable);
        
        // Enter key support
        document.getElementById('certificateIdInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('validateBtn').click();
            }
        });
        
        // Close modal on outside click
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('certModal');
            if (e.target === modal) closeModal();
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('certModal');
                if (!modal.classList.contains('hidden')) closeModal();
            }
        });
    </script>
</body>

</html>