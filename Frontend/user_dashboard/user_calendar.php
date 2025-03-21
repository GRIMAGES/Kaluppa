<?php
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch events
$eventsQuery = "SELECT event_time, title FROM events ORDER BY event_time ASC";
$eventsResult = $conn->query($eventsQuery);

$events = [];
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

// Fetch courses (Scholarships)
$coursesQuery = "SELECT start_date, end_date, name FROM courses ORDER BY start_date ASC";
$coursesResult = $conn->query($coursesQuery);

if ($coursesResult->num_rows > 0) {
    while ($course = $coursesResult->fetch_assoc()) {
        $startDate = new DateTime($course['start_date']);
        $endDate = new DateTime($course['end_date']);

        // Add event for the start date
        $events[] = [
            'title' => $course['name'] . ' (Start)',
            'start' => $startDate->format('Y-m-d'),
            'description' => 'Scholarship Start: ' . $course['name'],
            'type' => 'scholarship'
        ];

        // Add event for the end date
        $events[] = [
            'title' => $course['name'] . ' (End)',
            'start' => $endDate->format('Y-m-d'),
            'description' => 'Scholarship End: ' . $course['name'],
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
    <title>Calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/user_css/calendar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

<!-- Calendar Section -->
<div class="calendar-container">
    <h3 class="mb-3" style="color: white;">Event Calendar</h3>
    <div class="calendar-wrapper">
        <div id="calendar"></div>
    </div>
</div>



<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content fade-in">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 id="eventTitle"></h5>
                <p id="eventDescription"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: <?php echo json_encode($events); ?>, // Pass PHP array to JavaScript
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
            },
            eventContent: function(arg) {
                // Add a custom class based on the event type
                var eventType = arg.event.extendedProps.description.startsWith('Course:') ? 'course-event' : 'regular-event';
                return { 
                    html: `<div class="${eventType}">${arg.event.title}</div>`
                };
            }
        });
        calendar.render();

        // Filter events based on the selected type and time
        function filterEvents() {
            var selectedType = document.getElementById('eventFilter').value;
            var selectedTime = document.getElementById('timeFilter').value;
            var filteredEvents = <?php echo json_encode($events); ?>.filter(function(event) {
                var matchesType = selectedType === 'all' || event.description.toLowerCase().includes(selectedType);
                var eventTime = new Date(event.start).getHours();
                var matchesTime = selectedTime === 'all' || 
                    (selectedTime === 'morning' && eventTime >= 5 && eventTime < 12) ||
                    (selectedTime === 'afternoon' && eventTime >= 12 && eventTime < 17) ||
                    (selectedTime === 'evening' && eventTime >= 17 && eventTime < 21) ||
                    (selectedTime === 'night' && (eventTime >= 21 || eventTime < 5));
                return matchesType && matchesTime;
            });
            calendar.removeAllEvents();
            calendar.addEventSource(filteredEvents);
        }

        document.getElementById('eventFilter').addEventListener('change', filterEvents);
        document.getElementById('timeFilter').addEventListener('change', filterEvents);
    });

    // Make the calendar resizable
    document.addEventListener('DOMContentLoaded', function () {
        var calendarContainer = document.querySelector('.calendar-container');
        calendarContainer.style.resize = 'both';
        calendarContainer.style.overflow = 'auto';
    });
</script>
</body>
</html>
