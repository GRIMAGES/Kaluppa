<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
require_once '../../Frontend/vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}



// Calculate load time
$loadTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];

// Initialize performance data array
$performanceData = [];
$adminEmail = $_SESSION['email'] ?? ''; // Handle undefined array key
// Fetch the admin's full name from the user table
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? ''; // Handle undefined array key
$stmt->close();
// Track query execution times
$startTime = microtime(true);
$queryUsers = "SELECT COUNT(*) AS total_users FROM user";
$totalUsers = $conn->query($queryUsers)->fetch_assoc()['total_users'];
$userQueryTime = microtime(true) - $startTime;

$startTime = microtime(true);
$queryApplications = "SELECT COUNT(*) AS total_applications FROM applications"; // Added missing $
$totalApplications = $conn->query($queryApplications)->fetch_assoc()['total_applications'];
$applicationQueryTime = microtime(true) - $startTime;

$startTime = microtime(true);
$queryVolunteers = "SELECT COUNT(*) AS total_volunteers FROM volunteer_application";
$totalVolunteers = $conn->query($queryVolunteers)->fetch_assoc()['total_volunteers'];
$volunteerQueryTime = microtime(true) - $startTime;

// Fetch yearly data for scholarship applications
$scholarshipYearlyData = [];
$queryScholarshipYearly = "SELECT YEAR(applied_at) AS year, COUNT(*) AS total FROM applications GROUP BY YEAR(applied_at)";
$resultScholarshipYearly = $conn->query($queryScholarshipYearly);
while ($row = $resultScholarshipYearly->fetch_assoc()) {
    $scholarshipYearlyData[] = $row;
}

// Fetch yearly data for volunteer applications
$volunteerYearlyData = [];
$queryVolunteerYearly = "SELECT YEAR(application_date) AS year, COUNT(*) AS total FROM volunteer_application GROUP BY YEAR(application_date)";
$resultVolunteerYearly = $conn->query($queryVolunteerYearly);
while ($row = $resultVolunteerYearly->fetch_assoc()) {
    $volunteerYearlyData[] = $row;
}

// Prepare data for the chart
$years = array_unique(array_merge(
    array_column($scholarshipYearlyData, 'year'),
    array_column($volunteerYearlyData, 'year')
));
sort($years);

$scholarshipCounts = [];
$volunteerCounts = [];
foreach ($years as $year) {
    $scholarshipCounts[] = array_reduce($scholarshipYearlyData, function ($carry, $item) use ($year) {
        return $item['year'] == $year ? $item['total'] : $carry;
    }, 0);
    $volunteerCounts[] = array_reduce($volunteerYearlyData, function ($carry, $item) use ($year) {
        return $item['year'] == $year ? $item['total'] : $carry;
    }, 0);
}

$performanceData = [
    ['Metric', 'Value', 'Color'],
    ['Total Users', $totalUsers, '#2196F3'], // Blue for total users
    ['Total Applications', $totalApplications, '#4caf50'], // Green for total applications
    ['Total Volunteers', $totalVolunteers, '#FFC107'], // Yellow for total volunteers
    ['User Query Time (s)', $userQueryTime, '#FF5722'], // Orange for query times
    ['Application Query Time (s)', $applicationQueryTime, '#009688'], // Teal for query times
    ['Volunteer Query Time (s)', $volunteerQueryTime, '#673AB7'], // Purple for query times
    ['Total Load Time (s)', $loadTime, '#FFEB3B'], // Light Yellow for load time
];

// Separate labels and values
$labels = array_map(function($item) {
    return $item[0];
}, array_slice($performanceData, 1)); // Exclude header row

$values = array_map(function($item) {
    return $item[1];
}, array_slice($performanceData, 1)); // Exclude header row

$colors = array_map(function($item) {
    return $item[2];
}, array_slice($performanceData, 1)); // Exclude header row

// Fetch filtered data for scholarship and volunteer applications
$filter = $_GET['filter'] ?? 'yearly'; // Default to yearly
$scholarshipFilteredData = [];
$volunteerFilteredData = [];

if ($filter === 'daily') {
    $queryScholarshipFiltered = "SELECT DATE(applied_at) AS period, COUNT(*) AS total FROM applications GROUP BY DATE(applied_at)";
    $queryVolunteerFiltered = "SELECT DATE(application_date) AS period, COUNT(*) AS total FROM volunteer_application GROUP BY DATE(application_date)";
} elseif ($filter === 'weekly') {
    $queryScholarshipFiltered = "SELECT YEAR(applied_at) AS year, WEEK(applied_at) AS period, COUNT(*) AS total FROM applications GROUP BY YEAR(applied_at), WEEK(applied_at)";
    $queryVolunteerFiltered = "SELECT YEAR(application_date) AS year, WEEK(application_date) AS period, COUNT(*) AS total FROM volunteer_application GROUP BY YEAR(application_date), WEEK(application_date)";
} elseif ($filter === 'monthly') {
    $queryScholarshipFiltered = "SELECT YEAR(applied_at) AS year, MONTH(applied_at) AS period, COUNT(*) AS total FROM applications GROUP BY YEAR(applied_at), MONTH(applied_at)";
    $queryVolunteerFiltered = "SELECT YEAR(application_date) AS year, MONTH(application_date) AS period, COUNT(*) AS total FROM volunteer_application GROUP BY YEAR(application_date), MONTH(application_date)";
} else { // Default to yearly
    $queryScholarshipFiltered = "SELECT YEAR(applied_at) AS period, COUNT(*) AS total FROM applications GROUP BY YEAR(applied_at)";
    $queryVolunteerFiltered = "SELECT YEAR(application_date) AS period, COUNT(*) AS total FROM volunteer_application GROUP BY YEAR(application_date)";
}

$resultScholarshipFiltered = $conn->query($queryScholarshipFiltered);
while ($row = $resultScholarshipFiltered->fetch_assoc()) {
    $scholarshipFilteredData[] = $row;
}

$resultVolunteerFiltered = $conn->query($queryVolunteerFiltered);
while ($row = $resultVolunteerFiltered->fetch_assoc()) {
    $volunteerFilteredData[] = $row;
}

// Prepare data for the charts
$periods = array_unique(array_merge(
    array_column($scholarshipFilteredData, 'period'),
    array_column($volunteerFilteredData, 'period')
));
sort($periods);

$scholarshipCounts = [];
$volunteerCounts = [];
foreach ($periods as $period) {
    $scholarshipCounts[] = array_reduce($scholarshipFilteredData, function ($carry, $item) use ($period) {
        return $item['period'] == $period ? $item['total'] : $carry;
    }, 0);
    $volunteerCounts[] = array_reduce($volunteerFilteredData, function ($carry, $item) use ($period) {
        return $item['period'] == $period ? $item['total'] : $carry;
    }, 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .table {
            border-radius: 10px;
            border: 1px solid #4caf50;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table thead {
            background-color: #4caf50;
            color: white;
        }
        .table th, .table td {
            padding: 10px;
            text-align: center;
        }
        .table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .table tbody tr:hover {
            background-color: #e0e0e0;
        }
        .export-btn {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
            width: 100%;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color: red;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="logoutForm" action="admin_scholarship.php" method="POST">
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

                </form>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    <div class="d-flex justify-content-between">
        <div>
            <h2>Performance Analytics</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($performanceData as $index => $row): ?>
                        <?php if ($index === 0) continue; // Skip header row ?>
                        <tr>
                            <td><?= htmlspecialchars($row[0]) ?></td>
                            <td><?= htmlspecialchars($row[1]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <!-- Performance Analytics Chart -->
            <canvas id="performanceChart" width="400" height="200"></canvas>
        </div>
    </div>
    <!-- Add separate bar charts for scholarship and volunteer applications -->
    <div class="row mt-4">
        <div class="col-md-6">
            <h3>Scholarship Applications Analytics</h3>
            <canvas id="scholarshipApplicationsChart" width="400" height="200"></canvas>
        </div>
        <div class="col-md-6">
            <h3>Volunteer Applications Analytics</h3>
            <canvas id="volunteerApplicationsChart" width="400" height="200"></canvas>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <h3>Yearly Applications Analytics</h3>
            <canvas id="yearlyApplicationsChart" width="400" height="200"></canvas>
        </div>
    </div>
    <div class="row mt-4">
        <!-- Add filter dropdown -->
        <div class="col-md-12 mb-3">
            <form method="GET" action="">
                <label for="filter">Filter by:</label>
                <select name="filter" id="filter" class="form-select" onchange="this.form.submit()">
                    <option value="daily" <?= $filter === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="weekly" <?= $filter === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                </select>
            </form>
        </div>
        <div class="col-md-12">
            <h3>Scholarship and Volunteer Applications Analytics</h3>
            <canvas id="applicationsChart" width="400" height="200"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Prepare data for the performance chart
    var labels = <?= json_encode($labels) ?>; // Convert PHP array to JSON for Chart.js
    var values = <?= json_encode($values) ?>;
    var colors = <?= json_encode($colors) ?>;

    var performanceChart = new Chart(document.getElementById('performanceChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Performance Metrics',
                data: values,
                backgroundColor: colors,
                borderColor: colors.map(color => darken(color, 20)),
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Helper function to darken a color
    function darken(color, percent) {
        var R = parseInt(color.slice(1, 3), 16);
        var G = parseInt(color.slice(3, 5), 16);
        var B = parseInt(color.slice(5, 7), 16);
        R = Math.round(R * (1 - percent / 100));
        G = Math.round(G * (1 - percent / 100));
        B = Math.round(B * (1 - percent / 100));
        return `rgb(${R},${G},${B})`;
    }

    // Prepare data for the yearly applications chart
    var yearlyLabels = <?= json_encode($years) ?>;
    var yearlyScholarshipData = <?= json_encode($scholarshipCounts) ?>;
    var yearlyVolunteerData = <?= json_encode($volunteerCounts) ?>;

    var yearlyApplicationsChart = new Chart(document.getElementById('yearlyApplicationsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: yearlyLabels,
            datasets: [
                {
                    label: 'Scholarship Applications',
                    data: yearlyScholarshipData,
                    backgroundColor: '#4caf50',
                    borderColor: '#388e3c',
                    borderWidth: 1
                },
                {
                    label: 'Volunteer Applications',
                    data: yearlyVolunteerData,
                    backgroundColor: '#2196F3',
                    borderColor: '#1976D2',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Year'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Applications'
                    }
                }
            }
        }
    });

    // Prepare data for the scholarship applications chart
    var scholarshipLabels = <?= json_encode($periods) ?>;
    var filteredScholarshipData = <?= json_encode($scholarshipCounts) ?>;

    var scholarshipApplicationsChart = new Chart(document.getElementById('scholarshipApplicationsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: scholarshipLabels,
            datasets: [{
                label: 'Scholarship Applications',
                data: filteredScholarshipData,
                backgroundColor: '#4caf50',
                borderColor: '#388e3c',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Period'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Applications'
                    }
                }
            }
        }
    });

    // Prepare data for the volunteer applications chart
    var volunteerLabels = <?= json_encode($periods) ?>;
    var filteredVolunteerData = <?= json_encode($volunteerCounts) ?>;

    var volunteerApplicationsChart = new Chart(document.getElementById('volunteerApplicationsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: volunteerLabels,
            datasets: [{
                label: 'Volunteer Applications',
                data: filteredVolunteerData,
                backgroundColor: '#2196F3',
                borderColor: '#1976D2',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Period'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Applications'
                    }
                }
            }
        }
    });
</script>
</body>
</html>
