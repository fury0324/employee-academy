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
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id <= 0) {
    header("Location: dashboard.php?error=Invalid course ID");
    exit();
}

// Get referring page to determine back URL
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$back_url = 'dashboard.php';

if (strpos($referrer, 'general_courses.php') !== false) {
    $back_url = 'general_courses.php';
} elseif (strpos($referrer, 'certificates.php') !== false) {
    $back_url = 'certificates.php';
} elseif (strpos($referrer, 'upskilling.php') !== false) {
    $back_url = 'upskilling.php';
} elseif (strpos($referrer, 'course_player.php') !== false) {
    $back_url = 'dashboard.php';
}

if (isset($_GET['return_to'])) {
    $allowed_returns = ['general_courses', 'certificates', 'upskilling', 'dashboard'];
    if (in_array($_GET['return_to'], $allowed_returns)) {
        $back_url = $_GET['return_to'] . '.php';
    }
}

// Fetch user details
$user_stmt = $conn->prepare("SELECT firstname, lastname, email FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: dashboard.php?error=User not found");
    exit();
}

$firstname = ucwords(strtolower(trim($user['firstname'] ?? '')));
$lastname = ucwords(strtolower(trim($user['lastname'] ?? '')));
$fullname = $firstname . ' ' . $lastname;

// Fetch course details
$course_stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND status = 'published'");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: dashboard.php?error=Course not found");
    exit();
}

// Fetch or create certificate
$cert_stmt = $conn->prepare("SELECT * FROM certificates WHERE user_id = ? AND course_id = ?");
$cert_stmt->bind_param("ii", $user_id, $course_id);
$cert_stmt->execute();
$certificate = $cert_stmt->get_result()->fetch_assoc();

if (!$certificate) {
    $quiz_stmt = $conn->prepare("SELECT uqa.score, uqa.status FROM user_quiz_attempts uqa JOIN quizzes q ON uqa.quiz_id = q.id WHERE uqa.user_id = ? AND q.course_id = ?");
    $quiz_stmt->bind_param("ii", $user_id, $course_id);
    $quiz_stmt->execute();
    $quiz_results = $quiz_stmt->get_result();
    
    $scores = [];
    $total_quizzes = 0;
    $passed_quizzes = 0;
    
    while ($row = $quiz_results->fetch_assoc()) {
        $scores[] = $row['score'];
        $total_quizzes++;
        if ($row['status'] === 'passed') $passed_quizzes++;
    }
    
    $final_score = count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;
    $certificate_number = 'UPSTAFF-' . strtoupper(uniqid()) . '-' . date('Ymd');
    $issued_date = date('Y-m-d H:i:s');
    $expiry_date = date('Y-m-d', strtotime('+1 year'));
    
    $insert_stmt = $conn->prepare("INSERT INTO certificates (user_id, course_id, certificate_number, final_score, total_quizzes_passed, total_quizzes, issued_at, expiry_date) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
    $insert_stmt->bind_param("iisiiis", $user_id, $course_id, $certificate_number, $final_score, $passed_quizzes, $total_quizzes, $expiry_date);
    $insert_stmt->execute();
    
    $certificate = [
        'final_score' => $final_score,
        'total_quizzes_passed' => $passed_quizzes,
        'total_quizzes' => $total_quizzes,
        'certificate_number' => $certificate_number,
        'issued_at' => $issued_date,
        'expiry_date' => $expiry_date
    ];
}

$settings_stmt = $conn->prepare("SELECT passing_threshold FROM quiz_settings WHERE course_id = ?");
$settings_stmt->bind_param("i", $course_id);
$settings_stmt->execute();
$settings = $settings_stmt->get_result()->fetch_assoc();
$passing_threshold = $settings['passing_threshold'] ?? 70;

$issued_date_formatted = date('F d, Y', strtotime($certificate['issued_at']));
$expiry_date_formatted = date('F d, Y', strtotime($certificate['expiry_date']));

$page_title = "Certificate of Completion - " . htmlspecialchars($fullname);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0, user-scalable=yes" name="viewport" />
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&amp;family=Montserrat:wght@300;400;600;700&amp;family=Playfair+Display:wght@700&amp;display=swap"
        rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        .font-certificate-title {
            font-family: 'Cinzel', serif;
            letter-spacing: 0.1em;
            color: #0f172a;
        }

        .font-recipient-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #080E2C;
            text-transform: uppercase;
        }

        .font-body-text {
            font-family: 'Montserrat', sans-serif;
            color: #334155;
        }

        /* SCREEN STYLES */
        body {
            background: #e5e7eb;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }

        .button-container {
            width: 100%;
            max-width: 1000px;
            margin-bottom: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .certificate-container {
            width: 100%;
            max-width: 1000px;
            position: relative;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            background-image: url('../cert bg/general-bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .signature-line {
            border-top: 2px solid #080E2C;
            width: 200px;
            margin-bottom: 8px;
        }

        .certificate-content {
            padding: 60px 80px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            .certificate-content {
                padding: 30px 40px;
            }
            .font-recipient-name {
                font-size: 1.3rem;
            }
            .signature-line {
                width: 100px;
            }
            .flex.justify-center {
                gap: 1.5rem;
            }
            .button-container {
                flex-direction: column;
                align-items: flex-end;
            }
        }

        /* IMPROVED PRINT STYLES */
        @media print {
            @page {
                size: landscape;
                margin: 0.3in;
            }

            html, body {
                height: auto;
                margin: 0;
                padding: 0;
                background: white;
            }

            body {
                display: block;
                padding: 0 !important;
                margin: 0 !important;
                background: white;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print {
                display: none !important;
            }

            .certificate-container {
                position: relative;
                box-shadow: none;
                margin: 0 auto;
                padding: 0;
                width: 100%;
                border: none;
                page-break-after: avoid;
                page-break-before: avoid;
                page-break-inside: avoid;
                break-inside: avoid;
                background-image: url('../cert bg/general-bg.png') !important;
                background-size: cover !important;
                background-position: center !important;
            }
            
            .button-container {
                display: none !important;
            }
            
            .certificate-content {
                padding: 40px 60px !important;
            }
            
            .font-certificate-title h1 {
                font-size: 36pt !important;
                margin-bottom: 10pt !important;
            }
            
            .font-certificate-title h2 {
                font-size: 22pt !important;
                margin-bottom: 20pt !important;
            }
            
            .font-recipient-name {
                font-size: 24pt !important;
                margin: 12pt 0 !important;
            }
            
            .font-body-text {
                font-size: 11pt !important;
                line-height: 1.4 !important;
            }
            
            .font-body-text.text-sm {
                font-size: 9pt !important;
            }
            
            .signature-line {
                width: 160px !important;
                border-top: 2px solid #080E2C !important;
            }
            
            .flex.justify-center {
                gap: 4rem !important;
            }
            
            .border-t {
                border-top: 1px solid #9ca3af !important;
                margin-top: 16pt !important;
                padding-top: 8pt !important;
            }
        }

        .btn-print, .btn-back {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
        }

        .btn-print {
            background-color: #2563eb;
            color: white;
            border: none;
        }

        .btn-print:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-back {
            background-color: #6b7280;
            color: white;
        }

        .btn-back:hover {
            background-color: #4b5563;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>

    <div class="button-container no-print">
        <button onclick="window.print()" class="btn-print">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                </path>
            </svg>
            Print / Save as PDF
        </button>
        <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn-back">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
            Back to Dashboard
        </a>
    </div>

    <div class="certificate-container" id="certificate">
        <div class="certificate-content">
            <div class="mb-8">
                <h1 class="font-certificate-title text-6xl font-bold mb-4 pt-16">CERTIFICATE</h1>
                <h2 class="font-certificate-title text-3xl font-normal uppercase tracking-[0.2em]">Of Completion</h2>
            </div>

            <div class="mb-6">
                <p class="font-body-text italic text-lg mb-4">This certificate is proudly presented to</p>
                <div>
                    <h3 class="font-recipient-name"><?php echo strtoupper(htmlspecialchars($fullname)); ?></h3>
                    <div class="w-10/12 h-[1px] bg-slate-300 mx-auto mt-1"></div>
                </div>
            </div>

            <div class="max-w-2xl mx-auto">
                <p class="font-body-text text-base leading-relaxed">
                    for successfully completing the course <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                    with a final score of <strong><?php echo $certificate['final_score']; ?>%</strong>
                    (<?php echo $certificate['total_quizzes_passed']; ?>/<?php echo $certificate['total_quizzes']; ?> quizzes passed).
                </p>
                <p class="font-body-text text-sm text-gray-600 mt-3">
                    Course Category: <?php echo htmlspecialchars($course['category'] ?? 'General'); ?> |
                    Difficulty: <?php echo htmlspecialchars($course['difficulty'] ?? 'Beginner'); ?>
                </p>
            </div>

            <div class="flex justify-center gap-28 mt-10">
                <div class="flex flex-col items-center">
                    <div class="signature-line"></div>
                    <p class="font-body-text font-bold text-sm tracking-wide">ARIEL GORDON</p>
                    <p class="font-body-text text-[10px] uppercase tracking-widest text-slate-500">CEO</p>
                </div>
                <div class="flex flex-col items-center">
                    <div class="signature-line"></div>
                    <p class="font-body-text font-bold text-sm tracking-wide">RAINALYN ALCUIZAR</p>
                    <p class="font-body-text text-[10px] uppercase tracking-widest text-slate-500">REPRESENTATIVE OFFICE</p>
                </div>
            </div>

            <div class="mt-8 pt-4 border-t border-gray-500">
                <p class="font-body-text text-[10px] text-gray-600">
                    Certificate No: <?php echo htmlspecialchars($certificate['certificate_number']); ?> |
                    Issued: <?php echo $issued_date_formatted; ?> |
                    Valid Until: <?php echo $expiry_date_formatted; ?>
                </p>
            </div>
        </div>
    </div>
</body>

</html>