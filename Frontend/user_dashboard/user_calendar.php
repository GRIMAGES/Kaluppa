<?php
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch Events
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

// Fetch Courses (Scholarships)
$coursesQuery = "SELECT start_date, end_date, name FROM courses ORDER BY start_date ASC";
$coursesResult = $conn->query($coursesQuery);
if ($coursesResult->num_rows > 0) {
    while ($course = $coursesResult->fetch_assoc()) {
        $events[] = [
            'title' => $course['name'] . ' (Start)',
            'start' => $course['start_date'],
            'description' => 'Scholarship Start: ' . $course['name'],
            'type' => 'scholarship'
        ];
        $events[] = [
            'title' => $course['name'] . ' (End)',
            'start' => $course['end_date'],
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
    <title>Event Calendar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap & FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/main.min.css" rel="stylesheet">
<style>
/* General Body and Layout */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #ddead1; /* Match page background color */
    display: flex;
    justify-content: flex-start; /* Align items to the left */
    align-items: flex-start; /* Align items to the top */
    height: 100vh;
    overflow: hidden;
    padding-top: 20px; /* Add padding to move content down */
}

/* Calendar Container */
.calendar-container {
    width: 80%; /* Decrease width to avoid sidebar overlap */
    background: linear-gradient(to right, rgb(2, 61, 15), rgb(26, 70, 41));
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-top: 100px; /* Increase top margin to move below the topbar */
    margin-left: 200px; /* Increase this value to move the calendar further to the right */
    animation: slideIn 0.6s ease-in-out;
    resize: both; /* Make the container resizable */
    overflow: auto; /* Ensure content is scrollable if resized */
    position: relative; /* Ensure it is not behind the sidebar */
    z-index: 1; /* Ensure it is not behind the sidebar */
}

/* Calendar Wrapper */
.calendar-wrapper {
    width: 100%;
    height: 500px; /* Adjust calendar height */
    margin: 0 auto;
    border-radius: 10px;
    background: rgb(9, 17, 2); /* Update sidebar background color */
    padding: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.8s ease-in-out;
}

#calendar {
    width: 100%;
    height: 100%;
    background: #ddead1; /* Ensure calendar background color is changed */
    border-radius: 10px;
}

/* Calendar Toolbar */
.fc-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.fc-toolbar-title {
    font-family: 'Roboto', sans-serif;
    font-weight: 600;
    font-size: 18px; /* Adjust font size */
    color: white; /* Match text color */
}

.fc-button {
    background-color: #4CAF50; /* Match button background color */
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.fc-button:hover {
    background-color: #388E3C; /* Darker green on hover */
    transform: scale(1.05); /* Slightly enlarge on hover */
}

.fc-button-primary {
    background-color: #4CAF50; /* Match button background color */
    border: none;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.fc-button-primary:hover {
    background-color: #388E3C; /* Darker green on hover */
    transform: scale(1.05); /* Slightly enlarge on hover */
}

/* Calendar Events */
.fc-event {
    border-radius: 8px;
    background-color: #4CAF50; /* Match event background color */
    color: white;
    padding: 5px;
    font-size: 12px; /* Adjust font size */
    transition: background-color 0.3s ease, transform 0.3s ease;
}

.fc-event:hover {
    background-color: #388E3C; /* Darker green on hover */
    transform: scale(1.05); /* Slightly enlarge on hover */
}

/* Calendar Day Headers */
.fc-col-header-cell {
    background-color: #4b6043; /* Match header background color */
    color: white; /* Match text color */
    padding: 10px;
    border-radius: 5px;
}

/* Calendar Day Cells */
.fc-daygrid-day {
    background-color: #ddead1; /* Match day cell background color */
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.fc-daygrid-day:hover {
    background-color: #c8e6c9; /* Lighter green on hover */
}

/* Calendar Today Highlight */
.fc-day-today {
    background-color: #81c784; /* Highlight today's date */
    border-radius: 5px;
}

/* Advanced Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.modal-content {
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    border: none;
}

.modal-header {
    background-color: #1a4629;
    color: white;
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
}

.custom-modal .modal-body {
    background-color: #f9f9f9;
    color: #333;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
}

.modal-footer.justify-content-center .btn-theme {
    background-color: rgb(2, 61, 15);
    border: none;
    color: white; /* Change text color to white */
}

/* Add styles for course start and end events */
.course-event {
    background-color: yellow !important; /* Set course events to yellow */
    color: black !important; /* Ensure text is readable */
    border-radius: 8px;
    padding: 5px;
    font-size: 12px;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

/* Add styles for regular events */
.regular-event {
    background-color: #4CAF50 !important; /* Set regular events to green */
    color: white !important;
    border-radius: 8px;
    padding: 5px;
    font-size: 12px;
    transition: background-color 0.3s ease, transform 0.3s ease;
}


</style>
</head>
<body style="background: #ddead1;">

<!-- Include Sidebar and Topbar -->
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<!-- Main Content Wrapper -->
<div class="main-content">
    <div class="container-fluid">
        <h3 class="text-success fw-bold mb-3">📅 Event Calendar</h3>

        <!-- Filter Section -->
        <div class="filter-box">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Filter by Event Type:</label>
                    <select class="form-select" id="eventFilter">
                        <option value="all">All</option>
                        <option value="event">Event</option>
                        <option value="scholarship">Scholarship</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Filter by Time:</label>
                    <select class="form-select" id="timeFilter">
                        <option value="all">All Day</option>
                        <option value="morning">Morning (5AM - 12PM)</option>
                        <option value="afternoon">Afternoon (12PM - 5PM)</option>
                        <option value="evening">Evening (5PM - 9PM)</option>
                        <option value="night">Night (9PM - 5AM)</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-success" id="applyFilterBtn">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div id="calendar"></div>
    </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content fade-in">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 id="eventTitle"></h5>
                <p id="eventDescription"></p>
            </div>
        </div>
    </div>
</div>

<!-- JS Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var events = <?php echo json_encode($events); ?>;

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            events: events,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            eventClick: function(info) {
                document.getElementById('eventTitle').innerText = info.event.title;
                document.getElementById('eventDescription').innerText = info.event.extendedProps.description;
                new bootstrap.Modal(document.getElementById('eventModal')).show();
            }
        });

        calendar.render();

        function filterEvents() {
            var typeFilter = document.getElementById('eventFilter').value;
            var timeFilter = document.getElementById('timeFilter').value;

            var filtered = events.filter(function(event) {
                var typeMatch = typeFilter === 'all' || event.type === typeFilter;
                var timeMatch = true;

                if (timeFilter !== 'all') {
                    var hour = new Date(event.start).getHours();
                    if (timeFilter === 'morning') timeMatch = hour >= 5 && hour < 12;
                    else if (timeFilter === 'afternoon') timeMatch = hour >= 12 && hour < 17;
                    else if (timeFilter === 'evening') timeMatch = hour >= 17 && hour < 21;
                    else if (timeFilter === 'night') timeMatch = hour >= 21 || hour < 5;
                }

                return typeMatch && timeMatch;
            });

            calendar.removeAllEvents();
            calendar.addEventSource(filtered);
        }

        document.getElementById('eventFilter').addEventListener('change', filterEvents);
        document.getElementById('timeFilter').addEventListener('change', filterEvents);
        document.getElementById('applyFilterBtn').addEventListener('click', filterEvents);
    });
</script>

</body>
</html>
