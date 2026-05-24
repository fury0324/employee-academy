<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['firstname'] ?? 'Admin';

// Get filter parameters
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch ONLY certificates (one record per completed course)
$sql = "SELECT 
            c.id as record_id,
            u.firstname,
            u.lastname,
            cr.title as course_name,
            DATE(c.issued_at) as completion_date,
            c.final_score as percentage,
            'Pass' as status
        FROM certificates c
        JOIN users u ON c.user_id = u.id
        JOIN courses cr ON c.course_id = cr.id
        WHERE u.role = 'employee' AND cr.status = 'published'
        ORDER BY c.issued_at DESC";

$result = $conn->query($sql);
$reportData = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Apply search filter if needed
        if ($searchTerm) {
            $searchLower = strtolower($searchTerm);
            $name = strtolower($row['firstname'] . ' ' . $row['lastname']);
            $course = strtolower($row['course_name']);
            if (strpos($name, $searchLower) !== false || strpos($course, $searchLower) !== false) {
                $reportData[] = $row;
            }
        } else {
            $reportData[] = $row;
        }
    }
}

// Calculate statistics
$totalRecords = count($reportData);
$passCount = $totalRecords; // Lahat ng certificates ay Pass
$failCount = 0;
$passCountByCourse = [];

foreach ($reportData as $record) {
    $courseName = $record['course_name'];
    if (!isset($passCountByCourse[$courseName])) {
        $passCountByCourse[$courseName] = 0;
    }
    $passCountByCourse[$courseName]++;
}

$courseNames = array_keys($passCountByCourse);
$passCounts = array_values($passCountByCourse);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Training Completion Report | Upstaff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        .main-content {
            margin-left: 16rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            margin-top: 70px;
        }
        
        .main-content.sidebar-collapsed {
            margin-left: 5rem;
        }
        
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
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
            }
            .fixed-header {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
        
        .tab-active {
            background-color: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .tab-active i {
            color: white;
        }

        .tab-inactive {
            background-color: white;
            color: #4b5563;
            border-color: #e5e7eb;
        }

        .tab-inactive:hover {
            background-color: #f9fafb;
        }

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

        .card-hover {
            transition: all 0.25s ease-in-out;
            cursor: pointer;
            border: 1px solid #f3f4f6;
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15), 0 4px 8px -4px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
            background-color: #fefefe;
        }
        
        .table-view, .cards-view {
            transition: opacity 0.3s ease;
        }
        .hidden-view {
            display: none;
        }
        
        .view-toggle-btn {
            transition: all 0.2s ease;
        }
        .view-toggle-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <div class="p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-chart-line text-blue-600"></i> Training Completion Report
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Employee course performance - Certificate based completions only</p>
                </div>
                
                <div class="flex gap-2 bg-white rounded-lg shadow-sm border border-gray-200 p-1">
                    <button id="tableViewBtn" class="view-toggle-btn active px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 bg-blue-600 text-white">
                        <i class="fas fa-table"></i> Table View
                    </button>
                    <button id="analyticsViewBtn" class="view-toggle-btn px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 text-gray-600 hover:bg-gray-100">
                        <i class="fas fa-chart-pie"></i> Analytical View
                    </button>
                </div>
            </div>
            
            <div class="mb-6">
                <div class="relative max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 text-sm"></i>
                    </div>
                    <input type="text" id="searchInput" placeholder="Search by employee name or course title..."
                        value="<?php echo htmlspecialchars($searchTerm); ?>"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                </div>
            </div>
            
            <div id="tableView" class="table-view">
                <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center flex-wrap gap-3">
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-list-check text-blue-500"></i>
                                Course Completion Records
                            </h2>
                            <span id="recordCountBadge" class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo $totalRecords; ?></span>
                        </div>
                        <button id="exportCsvBtn" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-150">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[900px] w-full text-sm text-left text-gray-700">
                            <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Employee Name</th>
                                    <th class="px-6 py-4">Course Name</th>
                                    <th class="px-6 py-4">Completion Date</th>
                                    <th class="px-6 py-4">Percentage (%)</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody id="reportTableBody" class="divide-y divide-gray-200">
                                <?php if (count($reportData) > 0): ?>
                                    <?php foreach ($reportData as $record): ?>
                                        <?php 
                                        $percentageColor = $record['percentage'] >= 70 ? "text-green-700 font-semibold" : "text-red-600 font-semibold";
                                        ?>
                                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-sm font-bold shadow-sm">
                                                        <?php echo strtoupper(substr($record['firstname'], 0, 1)); ?>
                                                    </div>
                                                    <div class="font-medium text-gray-800">
                                                        <?php echo htmlspecialchars($record['firstname'] . ' ' . $record['lastname']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <td class="px-6 py-4 font-medium text-gray-700"><?php echo htmlspecialchars($record['course_name']); ?></div>
                                            <td class="px-6 py-4 text-gray-600"><?php echo date('m/d/Y', strtotime($record['completion_date'])); ?></div>
                                            <td class="px-6 py-4 <?php echo $percentageColor; ?>"><?php echo round($record['percentage']); ?>%</div>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border bg-green-100 text-green-700 border-green-200">
                                                    <i class="fas fa-check-circle text-xs"></i> Pass
                                                </span>
                                            </div>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-12 text-gray-500">
                                            <i class="fas fa-inbox mr-2"></i> No matching completion records found
                                        </div>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="analyticsView" class="cards-view hidden-view">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Total Certificates</p>
                                <p id="analyticsTotalCount" class="text-2xl font-bold text-gray-800 mt-1"><?php echo $totalRecords; ?></p>
                            </div>
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-certificate text-blue-600 text-lg"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Passed</p>
                                <p id="analyticsPassCount" class="text-2xl font-bold text-green-600 mt-1"><?php echo $passCount; ?></p>
                            </div>
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600 text-lg"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5 card-hover">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium">Failed</p>
                                <p id="analyticsFailCount" class="text-2xl font-bold text-red-600 mt-1"><?php echo $failCount; ?></p>
                            </div>
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-times-circle text-red-600 text-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                        <h3 class="font-semibold text-gray-700 flex items-center gap-2 border-b pb-2 mb-4">
                            <i class="fas fa-chart-pie text-indigo-500"></i> Courses Distribution
                        </h3>
                        <div class="flex justify-center items-center" style="min-height: 280px;">
                            <canvas id="donutChartPassedCourses" width="350" height="280"
                                style="max-width: 100%; height: auto; max-height: 280px;"></canvas>
                        </div>
                        <p class="text-xs text-center text-gray-500 mt-3">Each slice represents a course. Slice size = number of employees who completed that course.</p>
                    </div>

                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-5">
                        <h3 class="font-semibold text-gray-700 flex items-center gap-2 border-b pb-2 mb-4">
                            <i class="fas fa-chart-column text-blue-500"></i> Completion Status
                        </h3>
                        <canvas id="statusChart" width="400" height="200" style="max-height: 240px; width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            
            <div id="emptyState" class="hidden text-center py-12">
                <div class="flex flex-col items-center">
                    <i class="fas fa-chart-line text-gray-400 text-5xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No records found</p>
                    <p class="text-gray-400 text-sm mt-1">Try adjusting your search</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const reportData = <?php echo json_encode($reportData); ?>;
        const courseNames = <?php echo json_encode($courseNames); ?>;
        const passCounts = <?php echo json_encode($passCounts); ?>;
        const totalRecords = <?php echo $totalRecords; ?>;
        const passCount = <?php echo $passCount; ?>;
        const failCount = <?php echo $failCount; ?>;

        let activeView = "table";
        let searchTerm = "<?php echo addslashes($searchTerm); ?>";
        let barChartInstance = null;
        let donutPassedInstance = null;

        const tableView = document.getElementById("tableView");
        const analyticsView = document.getElementById("analyticsView");
        const tableViewBtn = document.getElementById("tableViewBtn");
        const analyticsViewBtn = document.getElementById("analyticsViewBtn");
        const searchInput = document.getElementById("searchInput");
        const reportTableBody = document.getElementById("reportTableBody");
        const recordCountBadge = document.getElementById("recordCountBadge");
        const exportCsvBtn = document.getElementById("exportCsvBtn");
        const emptyState = document.getElementById("emptyState");

        const analyticsTotalCountSpan = document.getElementById("analyticsTotalCount");
        const analyticsPassCountSpan = document.getElementById("analyticsPassCount");
        const analyticsFailCountSpan = document.getElementById("analyticsFailCount");

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

        function getFilteredData() {
            if (!searchTerm.trim()) return [...reportData];
            const term = searchTerm.trim().toLowerCase();
            return reportData.filter(function(item) {
                const fullName = (item.firstname + ' ' + item.lastname).toLowerCase();
                const courseName = item.course_name.toLowerCase();
                return fullName.includes(term) || courseName.includes(term);
            });
        }

        function formatDate(dateStr) {
            if (!dateStr) return "N/A";
            const date = new Date(dateStr);
            return (date.getMonth() + 1) + '/' + date.getDate() + '/' + date.getFullYear();
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        function getAvatarInitial(name) {
            return name ? name.charAt(0).toUpperCase() : "U";
        }

        function renderTableView() {
            const filtered = getFilteredData();
            recordCountBadge.innerText = filtered.length + " record" + (filtered.length !== 1 ? 's' : '');
            
            if (filtered.length === 0) {
                reportTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-12 text-gray-500"><i class="fas fa-inbox mr-2"></i> No matching completion records found</td></tr>';
                emptyState.classList.remove('hidden');
                tableView.style.display = 'none';
                return;
            }
            
            emptyState.classList.add('hidden');
            tableView.style.display = '';
            
            let html = '';
            for (let i = 0; i < filtered.length; i++) {
                const item = filtered[i];
                const percentageColor = item.percentage >= 70 ? "text-green-700 font-semibold" : "text-red-600 font-semibold";
                const fullName = item.firstname + ' ' + (item.lastname || '');
                html += '<tr class="hover:bg-blue-50 transition-colors duration-150">' +
                    '<td class="px-6 py-4 whitespace-nowrap">' +
                        '<div class="flex items-center gap-3">' +
                            '<div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-sm font-bold shadow-sm">' + getAvatarInitial(fullName) + '</div>' +
                            '<div class="font-medium text-gray-800">' + escapeHtml(fullName) + '</div>' +
                        '</div>' +
                    '</td>' +
                    '<td class="px-6 py-4 font-medium text-gray-700">' + escapeHtml(item.course_name) + '</td>' +
                    '<td class="px-6 py-4 text-gray-600">' + formatDate(item.completion_date) + '</td>' +
                    '<td class="px-6 py-4 ' + percentageColor + '">' + Math.round(item.percentage) + '%</td>' +
                    '<td class="px-6 py-4"><span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border bg-green-100 text-green-700 border-green-200"><i class="fas fa-check-circle text-xs"></i> Pass</span></td>' +
                '</tr>';
            }
            reportTableBody.innerHTML = html;
        }

        function exportToCSV() {
            const filteredData = getFilteredData();
            if (filteredData.length === 0) {
                alert("No data to export. Please adjust search filter.");
                return;
            }
            const headers = ["Employee Name", "Course Name", "Completion Date", "Percentage (%)", "Status"];
            let csvContent = headers.join(",") + "\n";
            for (let i = 0; i < filteredData.length; i++) {
                const item = filteredData[i];
                csvContent += '"' + (item.firstname + ' ' + item.lastname).replace(/"/g, '""') + '",';
                csvContent += '"' + item.course_name.replace(/"/g, '""') + '",';
                csvContent += formatDate(item.completion_date) + ',';
                csvContent += Math.round(item.percentage) + ',';
                csvContent += item.status + "\n";
            }
            const blob = new Blob(["\uFEFF" + csvContent], { type: "text/csv;charset=utf-8;" });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.setAttribute("download", "training_completion_report.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        function updateAnalyticsView() {
            const filtered = getFilteredData();
            const total = filtered.length;
            let passCountFiltered = 0;
            for (let i = 0; i < filtered.length; i++) {
                if (filtered[i].status === "Pass") passCountFiltered++;
            }
            const failCountFiltered = total - passCountFiltered;

            analyticsTotalCountSpan.innerText = total;
            analyticsPassCountSpan.innerText = passCountFiltered;
            analyticsFailCountSpan.innerText = failCountFiltered;

            if (total === 0) {
                emptyState.classList.remove('hidden');
                analyticsView.style.display = 'none';
                return;
            }
            
            emptyState.classList.add('hidden');
            analyticsView.style.display = '';

            if (barChartInstance) barChartInstance.destroy();
            const barCtx = document.getElementById('statusChart').getContext('2d');
            barChartInstance = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['Pass', 'Fail'],
                    datasets: [{
                        label: 'Number of Completions',
                        data: [passCountFiltered, failCountFiltered],
                        backgroundColor: ['#22c55e', '#ef4444'],
                        borderRadius: 8,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'top', labels: { font: { size: 12 } } },
                        tooltip: { callbacks: { label: function(ctx) { return ctx.raw + ' records'; } } }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, title: { display: true, text: 'Count', font: { size: 11 } } },
                        x: { title: { display: true, text: 'Status', font: { size: 11 } } }
                    }
                }
            });

            const passCountByCourseLocal = new Map();
            for (let i = 0; i < filtered.length; i++) {
                const course = filtered[i].course_name;
                passCountByCourseLocal.set(course, (passCountByCourseLocal.get(course) || 0) + 1);
            }

            const courseNamesLocal = Array.from(passCountByCourseLocal.keys());
            const passCountsLocal = courseNamesLocal.map(function(course) { return passCountByCourseLocal.get(course); });

            if (donutPassedInstance) donutPassedInstance.destroy();
            const donutCtx = document.getElementById('donutChartPassedCourses').getContext('2d');

            if (courseNamesLocal.length === 0) {
                donutPassedInstance = new Chart(donutCtx, {
                    type: 'doughnut',
                    data: { labels: ['No Courses'], datasets: [{ data: [1], backgroundColor: ['#e5e7eb'], borderWidth: 0 }] },
                    options: { cutout: '60%', plugins: { legend: { position: 'bottom' } } }
                });
            } else {
                const colorPalette = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec489a', '#06b6d4', '#84cc16', '#f97316', '#6366f1', '#14b8a6', '#d946ef'];
                const backgroundColors = [];
                for (let i = 0; i < courseNamesLocal.length; i++) {
                    backgroundColors.push(colorPalette[i % colorPalette.length]);
                }

                donutPassedInstance = new Chart(donutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: courseNamesLocal,
                        datasets: [{
                            data: passCountsLocal,
                            backgroundColor: backgroundColors,
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 12,
                            cutout: '60%',
                            radius: '85%'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom', labels: {
                                    font: { size: 11 }, usePointStyle: true, boxWidth: 10, generateLabels: function(chart) {
                                        const original = Chart.defaults.plugins.legend.labels.generateLabels(chart);
                                        for (let i = 0; i < original.length; i++) {
                                            if (original[i].text !== 'No Courses') {
                                                original[i].text = original[i].text + ' (' + passCountsLocal[i] + ')';
                                            }
                                        }
                                        return original;
                                    }
                                }
                            },
                            tooltip: { callbacks: { label: function(ctx) { return ctx.label + ': ' + ctx.raw + ' completions'; } } }
                        },
                        layout: { padding: 10 }
                    }
                });
            }
        }

        function renderCurrentView() {
            if (activeView === "table") {
                renderTableView();
            } else {
                updateAnalyticsView();
            }
        }

        function setActiveView(view) {
            activeView = view;
            if (view === "table") {
                tableView.classList.remove('hidden-view');
                analyticsView.classList.add('hidden-view');
                tableViewBtn.classList.add('active', 'bg-blue-600', 'text-white');
                tableViewBtn.classList.remove('text-gray-600', 'hover:bg-gray-100');
                analyticsViewBtn.classList.remove('active', 'bg-blue-600', 'text-white');
                analyticsViewBtn.classList.add('text-gray-600', 'hover:bg-gray-100');
                renderTableView();
            } else {
                tableView.classList.add('hidden-view');
                analyticsView.classList.remove('hidden-view');
                analyticsViewBtn.classList.add('active', 'bg-blue-600', 'text-white');
                analyticsViewBtn.classList.remove('text-gray-600', 'hover:bg-gray-100');
                tableViewBtn.classList.remove('active', 'bg-blue-600', 'text-white');
                tableViewBtn.classList.add('text-gray-600', 'hover:bg-gray-100');
                updateAnalyticsView();
            }
        }

        function onSearchHandler() {
            searchTerm = searchInput.value;
            const url = new URL(window.location.href);
            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }
            window.history.pushState({}, '', url);
            renderCurrentView();
        }

        tableViewBtn.addEventListener("click", function() { setActiveView("table"); });
        analyticsViewBtn.addEventListener("click", function() { setActiveView("analytics"); });
        searchInput.addEventListener("input", onSearchHandler);
        if (exportCsvBtn) exportCsvBtn.addEventListener("click", exportToCSV);

        setActiveView("table");
    </script>
</body>
</html>