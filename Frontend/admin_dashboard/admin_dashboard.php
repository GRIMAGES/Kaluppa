<?php
require_once '../../Backend/connection.php';
session_start();
// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

// Check if the user has timed out due to inactivity
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)) {
    // Last activity was more than 30 minutes ago
    session_unset();     // unset $_SESSION variable for the run-time
    session_destroy();   // destroy session data
    header("Location: /Frontend/index.php");
    exit();
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();
// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}

// Initialize arrays
$userData = [];
$applicationData = [];
$volunteerData = [];
$dates = [];

// Fetch user creation data grouped by month
$queryUsers = "SELECT MONTH(created_at) AS month, YEAR(created_at) AS year, COUNT(*) AS count FROM user GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at), MONTH(created_at)";
$resultUsers = $conn->query($queryUsers);
while ($row = $resultUsers->fetch_assoc()) {
    $monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    $date = $monthNames[$row['month'] - 1] . ' ' . $row['year'];
    $dates[] = $date;
    $userData[$date] = (int)$row['count'];
}

// Fetch scholarship applications data grouped by month
$queryApplications = "SELECT MONTH(applied_at) AS month, YEAR(applied_at) AS year, COUNT(*) AS count FROM applications GROUP BY YEAR(applied_at), MONTH(applied_at) ORDER BY YEAR(applied_at), MONTH(applied_at)";
$resultApplications = $conn->query($queryApplications);
while ($row = $resultApplications->fetch_assoc()) {
    $monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    $date = $monthNames[$row['month'] - 1] . ' ' . $row['year'];
    if (!in_array($date, $dates)) {
        $dates[] = $date;
    }
    $applicationData[$date] = (int)$row['count'];
}

// Fetch volunteer applications data grouped by month
$queryVolunteerApplications = "SELECT MONTH(application_date) AS month, YEAR(application_date) AS year, COUNT(*) AS count FROM volunteer_application GROUP BY YEAR(application_date), MONTH(application_date) ORDER BY YEAR(application_date), MONTH(application_date)";
$resultVolunteerApplications = $conn->query($queryVolunteerApplications);
while ($row = $resultVolunteerApplications->fetch_assoc()) {
    $monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    $date = $monthNames[$row['month'] - 1] . ' ' . $row['year'];
    if (!in_array($date, $dates)) {
        $dates[] = $date;
    }
    $volunteerData[$date] = (int)$row['count'];
}

$queryEvents = "SELECT title FROM events";
$resultEvents = $conn->query($queryEvents);

// Example of how to process and display the events
while ($row = $resultEvents->fetch_assoc()) {
    echo "<li>" . htmlspecialchars($row['title']) . "</li>";
}
// Sort dates and fill missing values with 0
sort($dates);
foreach ($dates as $date) {
    $userData[$date] = $userData[$date] ?? 0;
    $applicationData[$date] = $applicationData[$date] ?? 0;
    $volunteerData[$date] = $volunteerData[$date] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <style>
        .chart-container {
            width: 100%;
            height: 400px; /* Adjust as needed */
        }
        .card {
            border-radius: 15px; /* Rounder corners */
        }
        .card-header, .card-footer {
            border-radius: 15px 15px 0 0; /* Rounder corners for header */
        }
        .card-footer {
            border-radius: 0 0 15px 15px; /* Rounder corners for footer */
        }
    </style>
</head>
<body >
<?php include 'admin_sidebar.php'; ?>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

<div class="content" style="margin-left: 250px; padding: 20px;">
<div class="row mt-4 fade-in">
    <div class="col-md-4 mb-3">
        <div class="card shadow border-0">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                <h5 class="card-title fw-semibold text-white">Total Users</h5>
                <h2 class="text-white"><?php echo array_sum($userData); ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow border-0">
            <div class="card-body text-center">
                <i class="fas fa-graduation-cap fa-2x text-success mb-2"></i>
                <h5 class="card-title fw-semibold text-white">Scholarship Applications</h5>
                <h2 class="text-white"><?php echo array_sum($applicationData); ?></h2>
            </div> 
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow border-0">
            <div class="card-body text-center">
                <i class="fas fa-hands-helping fa-2x text-warning mb-2"></i>
                <h5 class="card-title fw-semibold text-white">Volunteer Applications</h5>
                <h2 class="text-white"><?php echo array_sum($volunteerData); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card shadow border-0">
            <div class="card-header text-center" style="background: linear-gradient(135deg,rgba(17, 77, 42, 0.9),rgb(16, 134, 65)); color: white;">
                <h5 class="mb-0">Activity Overview</h5>
            </div>
            <div class="card-body chart-container bg-light">
                <canvas id="dataChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card shadow border-0">
            <div class="card-header text-center" style="background: linear-gradient(135deg,rgba(17, 77, 42, 0.9),rgb(16, 134, 65)); color: white;">
                <h5 class="mb-0">Upcoming Events</h5>
            </div>
            <div class="card-body p-3 bg-light">
                <ul class="list-group list-group-flush">
                    <?php
                    if ($resultEvents->num_rows > 0) {
                        while ($event = $resultEvents->fetch_assoc()) {
                            echo "<li class='list-group-item d-flex align-items-center'>
                                    <i class='fas fa-calendar-alt text-primary me-2'></i>
                                    <span class='event-title'>" . htmlspecialchars($event['title']) . "</span>
                                  </li>";
                        }
                    } else {
                        echo "<li class='list-group-item text-muted text-center'>No upcoming events</li>";
                    }
                    ?>
                </ul>
            </div>
            <div class="card-footer text-center bg-light">
                <a href="events.php" class="btn btn-outline-primary btn-sm">View All Events</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var ctx = document.getElementById('dataChart').getContext('2d');
    var dataChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Users Created',
                data: <?php echo json_encode(array_values($userData)); ?>,
                borderColor: 'rgba(0, 123, 255, 1)',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                fill: true
            }, {
                label: 'Scholarship Applications',
                data: <?php echo json_encode(array_values($applicationData)); ?>,
                borderColor: 'rgba(23, 162, 184, 1)',
                backgroundColor: 'rgba(23, 162, 184, 0.2)',
                fill: true
            }, {
                label: 'Volunteer Applications',
                data: <?php echo json_encode(array_values($volunteerData)); ?>,
                borderColor: 'rgba(255, 193, 7, 1)',
                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'category',
                    beginAtZero: true
                },
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

