<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
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

// Fetch all volunteer applications for the logged-in user with work title
$volunteerQuery = "SELECT volunteer_application.status, volunteer_application.applied_at, works.title AS work_title 
                   FROM volunteer_application 
                   JOIN works ON volunteer_application.work_id = works.id 
                   WHERE volunteer_application.email = ? 
                   ORDER BY volunteer_application.applied_at DESC";
$volunteerStmt = $conn->prepare($volunteerQuery);
$volunteerStmt->bind_param("s", $email);
$volunteerStmt->execute();
$volunteerResult = $volunteerStmt->get_result();

$volunteerApplications = [];
while ($row = $volunteerResult->fetch_assoc()) {
    $volunteerApplications[] = $row;
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

// Fetch card content data
$queryCards = "SELECT * FROM featured_cards";
$resultCards = $conn->query($queryCards);
$cards = [];
while ($row = $resultCards->fetch_assoc()) {
    $cards[] = $row;
}

// Fetch the featured card details
$query = "SELECT * FROM featured_cards ORDER BY id ASC";
$result = $conn->query($query);
$featuredCards = $result->fetch_all(MYSQLI_ASSOC);

// Fetch events for the calendar
$events = [];
$eventsQuery = "SELECT event_time, title FROM events ORDER BY event_time ASC";
$eventsResult = $conn->query($eventsQuery);
if ($eventsResult->num_rows > 0) {
    while ($event = $eventsResult->fetch_assoc()) {
        $events[] = [
            'title' => $event['title'],
            'start' => $event['event_time'],
            'description' => 'Event: ' . $event['title'],
            'type' => 'event'
        ];
    }
}

// Fetch courses for the calendar
$coursesQuery = "SELECT start_date, end_date, name FROM courses ORDER BY start_date ASC";
$coursesResult = $conn->query($coursesQuery);
if ($coursesResult->num_rows > 0) {
    while ($course = $coursesResult->fetch_assoc()) {
        $events[] = [
            'title' => $course['name'] . ' (Start)',
            'start' => $course['start_date'],
            'description' => 'Course Start: ' . $course['name'],
            'type' => 'scholarship'
        ];
        $events[] = [
            'title' => $course['name'] . ' (End)',
            'start' => $course['end_date'],
            'description' => 'Course End: ' . $course['name'],
            'type' => 'scholarship'
        ];
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
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/main.min.css" rel="stylesheet">
</head>
<body style="background-color: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-theme text-white">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p>Are you sure you want to log out?</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

<!-- Featured Cards Section -->
<div class="container mt-5" style="margin-top: 200px;"> <!-- Further adjusted margin-top -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4"> <!-- Added Bootstrap classes for responsive layout and gaps -->
        <?php foreach ($featuredCards as $card): ?>
            <div class="col">
                <div class="featured-card d-flex flex-column justify-content-between">
                    <img src="../../Frontend/admin_dashboard/uploads/featured/<?php echo htmlspecialchars($card['image']); ?>" class="featured-card-img" alt="Card Image" style="width: 100%; height: 250px; object-fit: cover;"> <!-- Consistent image size -->
                    <div class="featured-card-body">
                        <h5 class="featured-card-title"><?php echo htmlspecialchars($card['title']); ?></h5>
                        <p class="featured-card-text"><?php echo htmlspecialchars($card['description']); ?></p>
                    </div>
                    <div class="text-center mb-3">
                        <a href="#" class="view-btn" data-bs-toggle="modal" data-bs-target="#featuredModal<?php echo $card['id']; ?>">View More</a>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade custom-modal" id="featuredModal<?php echo $card['id']; ?>" tabindex="-1" aria-labelledby="featuredModalLabel<?php echo $card['id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="featuredModalLabel<?php echo $card['id']; ?>"><?php echo htmlspecialchars($card['title']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="../../Frontend/admin_dashboard/uploads/featured/<?php echo htmlspecialchars($card['image']); ?>" class="img-fluid mb-3 rounded shadow" alt="Card Image" style="width: 100%; height: 250px; object-fit: cover;"> <!-- Consistent image size -->
                            <p><strong><?php echo nl2br(htmlspecialchars($card['description'])); ?></strong></p>
                            <!-- Add additional details here -->
                            <p><strong>Additional Details:</strong></p>
                            <div style="text-align: left; padding: 10px;">
                                <?php echo nl2br(htmlspecialchars($card['details'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
    
<!-- Main Content -->   
<div class="main-content">
    <div class="progress-container my-4">
        <h4> Scholarship Application Status Progress</h4>
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

    <div class="progress-container my-4">
        <h4>Volunteer Application Status Progress</h4>
        <?php foreach ($volunteerApplications as $application): ?>
            <?php 
                $progress = getProgress($application['status']);
                $status = ucfirst($application['status']);
                $workTitle = htmlspecialchars($application['work_title']);
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
                        <?php echo $workTitle; ?> - <?php echo $status; ?> - <?php echo $progress; ?>%
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Calendar Section -->
    <div class="calendar-container mt-5">
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
</div>

<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/index.global.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var events = <?php echo json_encode($events); ?>;

        // Color logic: Blue for scholarships, Yellow for events
        events = events.map(event => {
            if (event.type === 'event') {
                event.backgroundColor = '#f9c74f'; // Yellow
                event.borderColor = '#f9c74f';
                event.textColor = '#000'; // Dark text
            } else if (event.type === 'scholarship') {
                event.backgroundColor = '#4dabf7'; // Blue
                event.borderColor = '#4dabf7';
                event.textColor = '#fff'; // White text
            }
            return event;
        });

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: events,
            eventClick: function(info) {
                document.getElementById('eventTitle').innerText = info.event.title;
                document.getElementById('eventDescription').innerText = info.event.extendedProps.description;
                new bootstrap.Modal(document.getElementById('eventModal')).show();
            }
        });

        calendar.render();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
