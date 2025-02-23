<?php
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

$email = $_SESSION['email'];

// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Combine first, middle, and last name
    $fullName = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];
} else {
    echo "User not found.";
}
$courseQuery = "SELECT * FROM courses";
$courseResult = mysqli_query($conn, $courseQuery);

if (!$courseResult) {
    die("Error fetching courses: " . mysqli_error($conn));
}
// Fetch all applications for the logged-in user with course name
$applicationQuery = "SELECT applications.status, applications.applied_at, courses.name AS course_name 
                     FROM applications 
                     JOIN courses ON applications.course_id = courses.id 
                     WHERE applications.email = ? 
                     ORDER BY applications.applied_at DESC";
$applicationStmt = $conn->prepare($applicationQuery);
$applicationStmt->bind_param("s", $email);
$applicationStmt->execute();
$applicationResult = $applicationStmt->get_result();

$applications = [];
while ($row = $applicationResult->fetch_assoc()) {
    $applications[] = $row;
}

// Function to map status to progress percentage
function getProgress($status) {
    switch ($status) {
        case 'pending':
            return 20;
        case 'under review':
            return 40;
        case 'approved':
            return 60;
        case 'enrolled':
            return 100;
        case 'rejected':
            return 0;
        default:
            return 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/user_css/user_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body style="background-color: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
               
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Frontend/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="featured-section">
        <div class="featured-card">
            <h3 class="card-title">Featured Course</h3>
            <p>Agricultural Course</p>
            <a href="user_courses.php" class="btn btn-outline-success">More Courses</a>
        </div>
        <div class="featured-card">
            <h3 class="card-title">Featured Work</h3>
            <p>Tree Planting</p>
            <a href="user_work.php" class="btn btn-outline-success">More Volunteer</a>
        </div>
        <div class="featured-card">
            <h3 class="card-title">Featured Event</h3>
            <p>Community Event</p>
            <a href="user_events.php" class="btn btn-outline-success">More Events</a>
        </div>
    </div>

    <div class="progress-container my-4">
        <h4>Application Status Progress</h4>
        <?php foreach ($applications as $application): ?>
            <?php 
                $progress = getProgress($application['status']);
                $status = ucfirst($application['status']);
                $courseName = htmlspecialchars($application['course_name']);
            ?>
            <div class="progress mb-3" style="height: 35px; border-radius: 30px; overflow: hidden;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                    role="progressbar" 
                    style="width: <?php echo $progress; ?>%; 
                           background: <?php echo ($application['status'] == 'rejected') 
                           ? 'linear-gradient(90deg, #e74c3c, #e57373)' 
                           : 'linear-gradient(90deg, #4caf50, #81c784)'; ?> !important;"
                    aria-valuenow="<?php echo $progress; ?>"
                    aria-valuemin="0" 
                    aria-valuemax="100">
                    <span class="progress-label">
                        <?php echo $courseName; ?> - <?php echo $status; ?> - <?php echo $progress; ?>%
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

   <!-- Calendar Section -->
   <div class="calendar-container">
        <h3 class="mb-3">Event Calendar</h3>
        <div class="calendar-wrapper">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Event Description Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5 id="eventTitle"></h5>
                    <p id="eventDescription"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<!-- FullCalendar and Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/index.global.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: 'http://localhost:3000//Backend/user controller/get_events.php', // Endpoint for fetching events
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            editable: false,
            eventClick: function (info) {
                // Set the event title and description into the modal
                document.getElementById('eventTitle').innerText = info.event.title;
                document.getElementById('eventDescription').innerText = info.event.extendedProps.description;
                
                // Show the modal
                var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
                eventModal.show();
            }
        });
        calendar.render();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
