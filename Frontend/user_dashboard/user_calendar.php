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
    <title>Calendar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/main.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #ddead1;
        }
        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }
        .fc-toolbar-title {
            font-size: 1.5rem;
        }
        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center mb-3">
        <div class="col-12 text-center">
            <h3 class="text-dark fw-bold">Event Calendar</h3>
        </div>
    </div>

    <!-- Filters -->
    <div class="row justify-content-center">
        <div class="col-lg-10 filter-box">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Filter by Event Type:</label>
                    <select class="form-select" id="eventFilter">
                        <option value="all">All</option>
                        <option value="event">Event</option>
                        <option value="scholarship">Scholarship</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Filter by Time:</label>
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
    </div>

    <!-- Calendar -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div id="calendar" class="bg-white rounded shadow p-3 mt-3"></div>
        </div>
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
