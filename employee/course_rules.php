<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    header("Location: dashboard.php?error=Invalid course ID");
    exit();
}

// Fetch course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND status = 'published'");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: dashboard.php?error=Course not found");
    exit();
}

// Determine return URL based on course type
$return_url = 'dashboard.php';
if ($course['type'] === 'general') {
    $return_url = 'general_courses.php';
} elseif ($course['type'] === 'upskilling') {
    $return_url = 'upskilling.php';
}

$user_name = $_SESSION['firstname'] ?? 'Employee';
$page_title = htmlspecialchars($course['title']) . " - Course Rules";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo $page_title; ?> · UpStaff Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        
        @keyframes modalSlideIn {
            0% {
                opacity: 0;
                transform: scale(0.9) translateY(-30px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-40px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 5px rgba(37, 99, 235, 0.3);
            }
            50% {
                box-shadow: 0 0 20px rgba(37, 99, 235, 0.6);
            }
        }
        
        .modal-container {
            animation: modalSlideIn 0.5s cubic-bezier(0.34, 1.2, 0.64, 1) forwards;
        }
        
        .rule-card {
            animation: slideInLeft 0.5s ease-out forwards;
            opacity: 0;
        }
        
        .rule-card:nth-child(1) { animation-delay: 0.05s; }
        .rule-card:nth-child(2) { animation-delay: 0.1s; }
        .rule-card:nth-child(3) { animation-delay: 0.15s; }
        .rule-card:nth-child(4) { animation-delay: 0.2s; }
        .rule-card:nth-child(5) { animation-delay: 0.25s; }
        .rule-card:nth-child(6) { animation-delay: 0.3s; }
        .rule-card:nth-child(7) { animation-delay: 0.35s; }
        .rule-card:nth-child(8) { animation-delay: 0.4s; }
        .rule-card:nth-child(9) { animation-delay: 0.45s; }
        
        .checkbox-animation {
            transition: all 0.3s ease;
        }
        
        .btn-hover-effect {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .btn-hover-effect::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-hover-effect:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .rule-icon {
            transition: transform 0.3s ease;
        }
        
        .rule-card:hover .rule-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #bfdbfe;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #3b82f6;
        }
    </style>
</head>
<body class="bg-white min-h-screen flex items-center justify-center p-4">
    
    <!-- Modal Container -->
    <div class="modal-container relative max-w-5xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-200">
        
        <!-- Header with Solid Blue -->
        <div class="bg-blue-600 px-8 py-6 text-white">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                    <span class="material-symbols-outlined text-4xl">gavel</span>
                </div>
                <div>
                    <h1 class="text-3xl font-bold">Course Taking Rules</h1>
                    <p class="text-white/80 text-sm mt-1">Please read and agree to the following rules before starting <span class="font-semibold"><?php echo htmlspecialchars($course['title']); ?></span></p>
                </div>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="p-8 max-h-[60vh] overflow-y-auto">
            
            <!-- Video Lessons Section -->
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-blue-600 text-lg">smart_display</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">🎥 Video Lessons</h2>
                </div>
                <div class="space-y-3 pl-2">
                    <div class="rule-card p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="rule-icon w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-blue-600 text-sm">check_circle</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Required Completion</h3>
                                <p class="text-sm text-gray-600">Learners must watch the entire video lesson before it can be marked as completed.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-card p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="rule-icon w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-blue-600 text-sm">lock</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Auto-Lock Feature</h3>
                                <p class="text-sm text-gray-600">Once the "Mark as Watched" button is clicked, the video will automatically be locked and can no longer be accessed or replayed.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-card p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="rule-icon w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-blue-600 text-sm">block</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Strict Viewing Policy</h3>
                                <p class="text-sm text-gray-600">Skipping or fast-forwarding the video is not allowed to ensure full viewing of the content.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Course Failure Policy Section -->
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-orange-600 text-lg">warning</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">❌ Course Failure Policy</h2>
                </div>
                <div class="space-y-3 pl-2">
                    <div class="rule-card p-4 bg-orange-50 rounded-xl border-l-4 border-orange-500 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="rule-icon w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-orange-600 text-sm">schedule</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Retake Waiting Period</h3>
                                <p class="text-sm text-gray-600">If a learner fails the course, they must wait <span class="font-bold text-orange-600">3 months</span> before they are allowed to retake it.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-card p-4 bg-orange-50 rounded-xl border-l-4 border-orange-500 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="rule-icon w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-orange-600 text-sm">admin_panel_settings</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Admin Override</h3>
                                <p class="text-sm text-gray-600">Learners may request an earlier retake, which can be granted if approved by an administrator.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quiz Taking Rules Section -->
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-blue-600 text-lg">quiz</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">📝 Quiz Taking Rules</h2>
                </div>
                <div class="space-y-3 pl-2">
                    <div class="rule-card p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="rule-icon w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-blue-600 text-sm">pause_circle</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">No Pause Policy</h3>
                                <p class="text-sm text-gray-600">Once the quiz has started, it cannot be paused or stopped.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-card p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="rule-icon w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-blue-600 text-sm">content_copy</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">No Copy-Paste</h3>
                                <p class="text-sm text-gray-600">Copying and pasting are strictly prohibited during the quiz.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-card p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="rule-icon w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-blue-600 text-sm">timer</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Time Limit</h3>
                                <p class="text-sm text-gray-600">Each quiz has a set time limit. Learners must complete the quiz before the time expires.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="rule-card p-4 bg-blue-50 rounded-xl border-l-4 border-blue-500 hover:shadow-md transition">
                        <div class="flex items-start gap-3">
                            <div class="rule-icon w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-blue-600 text-sm">looks_one</span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-800">Single Attempt Rule</h3>
                                <p class="text-sm text-gray-600">If a learner fails the quiz, they will not be allowed to retake it.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer with Agreement -->
        <div class="border-t border-gray-200 bg-gray-50 px-8 py-5">
            <label class="flex items-center gap-3 cursor-pointer group">
                <input type="checkbox" id="agreeCheckbox" class="checkbox-animation w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2 transition">
                <span class="text-gray-700 group-hover:text-gray-900 transition">
                    I have read and agree to all the <span class="font-semibold text-blue-600">Course Taking Rules</span>
                </span>
            </label>
            
            <div class="flex gap-3 mt-6">
                <button id="cancelBtn" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition transform hover:scale-105 duration-200">
                    Cancel
                </button>
                <button id="proceedBtn" disabled class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-xl font-medium transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 hover:scale-105 hover:shadow-lg btn-hover-effect">
                    <span class="flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">play_arrow</span>
                        Proceed to Course
                    </span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Get elements
        const agreeCheckbox = document.getElementById('agreeCheckbox');
        const proceedBtn = document.getElementById('proceedBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const courseId = <?php echo $course_id; ?>;
        const returnUrl = '<?php echo $return_url; ?>';
        
        // Enable/disable proceed button based on checkbox
        agreeCheckbox.addEventListener('change', function() {
            proceedBtn.disabled = !this.checked;
            if (this.checked) {
                playClickSound();
                createMiniConfetti();
            }
        });
        
        // Proceed button click handler
        proceedBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!agreeCheckbox.checked) {
                showToast('Please agree to the rules first', 'warning');
                return;
            }
            
            showSuccessAnimation();
            
            // Store agreement in localStorage
            localStorage.setItem(`course_rules_accepted_${courseId}`, 'true');
            localStorage.setItem(`course_rules_accepted_at_${courseId}`, new Date().toISOString());
            
            // Redirect to course player after animation
            setTimeout(() => {
                window.location.href = `course_player.php?id=${courseId}`;
            }, 1500);
        });
        
        // Cancel button - go back to previous page based on course type
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Add shake animation
            cancelBtn.style.animation = 'shake 0.3s ease';
            setTimeout(() => {
                cancelBtn.style.animation = '';
            }, 300);
            
            // Redirect to the appropriate courses page
            window.location.href = returnUrl;
        });
        
        // Play click sound effect
        function playClickSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 880;
                gainNode.gain.value = 0.1;
                
                oscillator.start();
                gainNode.gain.exponentialRampToValueAtTime(0.00001, audioContext.currentTime + 0.3);
                oscillator.stop(audioContext.currentTime + 0.3);
                
                setTimeout(() => audioContext.close(), 500);
            } catch(e) {
                // Silently fail if audio not supported
            }
        }
        
        // Create mini confetti effect
        function createMiniConfetti() {
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'fixed w-2 h-2 rounded-full pointer-events-none z-50';
                confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 70%, 60%)`;
                confetti.style.left = Math.random() * window.innerWidth + 'px';
                confetti.style.top = -10 + 'px';
                confetti.style.position = 'fixed';
                document.body.appendChild(confetti);
                
                const animation = confetti.animate([
                    { transform: 'translateY(0px) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg)`, opacity: 0 }
                ], {
                    duration: 1000 + Math.random() * 500,
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                });
                
                animation.onfinish = () => confetti.remove();
            }
        }
        
        // Show success animation
        function showSuccessAnimation() {
            const overlay = document.createElement('div');
            overlay.className = 'fixed inset-0 bg-green-500/20 backdrop-blur-sm z-50 flex items-center justify-center';
            overlay.style.animation = 'fadeIn 0.3s ease';
            
            const successCard = document.createElement('div');
            successCard.className = 'bg-white rounded-2xl p-8 text-center transform scale-0';
            successCard.style.animation = 'modalSlideIn 0.4s ease forwards';
            successCard.innerHTML = `
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-green-600 text-4xl">check_circle</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Rules Accepted!</h3>
                <p class="text-gray-600">Redirecting to course player...</p>
                <div class="mt-4">
                    <div class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
            `;
            
            overlay.appendChild(successCard);
            document.body.appendChild(overlay);
            startConfetti();
            
            setTimeout(() => {
                overlay.remove();
            }, 1500);
        }
        
        // Show toast message
        function showToast(message, type = 'warning') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-8 left-1/2 transform -translate-x-1/2 z-50 px-6 py-3 rounded-xl text-white text-sm font-medium shadow-lg transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : 'bg-orange-500'
            }`;
            toast.innerHTML = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-50%) translateY(20px)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
        
        // Full confetti effect
        function startConfetti() {
            const canvas = document.createElement('canvas');
            canvas.id = 'confettiCanvas';
            canvas.style.position = 'fixed';
            canvas.style.top = '0';
            canvas.style.left = '0';
            canvas.style.width = '100%';
            canvas.style.height = '100%';
            canvas.style.pointerEvents = 'none';
            canvas.style.zIndex = '1000';
            document.body.appendChild(canvas);
            
            const ctx = canvas.getContext('2d');
            
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            
            const particles = [];
            const colors = ['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ff9900', '#9900ff', '#ff69b4', '#3b82f6'];
            
            for (let i = 0; i < 200; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height - canvas.height,
                    size: Math.random() * 8 + 4,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    speedX: (Math.random() - 0.5) * 5,
                    speedY: Math.random() * 10 + 6,
                    rotation: Math.random() * 360,
                    rotationSpeed: (Math.random() - 0.5) * 15
                });
            }
            
            let animationId;
            let startTime = Date.now();
            
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                let allFinished = true;
                for (let p of particles) {
                    if (p.y < canvas.height + 100) {
                        allFinished = false;
                        p.x += p.speedX;
                        p.y += p.speedY;
                        p.rotation += p.rotationSpeed;
                        
                        ctx.save();
                        ctx.translate(p.x, p.y);
                        ctx.rotate(p.rotation * Math.PI / 180);
                        ctx.fillStyle = p.color;
                        ctx.fillRect(-p.size/2, -p.size/2, p.size, p.size);
                        ctx.restore();
                    }
                }
                
                if (!allFinished && Date.now() - startTime < 4000) {
                    animationId = requestAnimationFrame(animate);
                } else {
                    cancelAnimationFrame(animationId);
                    canvas.remove();
                }
            }
            
            animate();
        }
        
        // Keyboard support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !proceedBtn.disabled) {
                proceedBtn.click();
            }
            if (e.key === 'Escape') {
                cancelBtn.click();
            }
        });
        
        // Focus on checkbox for accessibility
        setTimeout(() => {
            agreeCheckbox.focus();
        }, 100);
    </script>
</body>
</html>