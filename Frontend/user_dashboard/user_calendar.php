<?php
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
            color: #212529;
        }
        .main-content {
            margin-left: 250px;
            padding: 60px 20px;
        }
        .fc-toolbar-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #198754;
        }
        .filter-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #198754;
        }
        #calendar {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .modal-content {
            border-radius: 10px;
        }
        .modal-header {
            background-color: #198754;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .modal-body {
            background-color: #f8f9fa;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 40px 15px;
            }
        }
    </style>
</head>
<body style="background: #ddead1;">

<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
    <h3 class="text-success fw-bold mb-3">📅 Event Calendar</h3>

<!-- Legend Start -->
<div class="mb-3">
    <h5 class="fw-semibold">Legend:</h5>
    <div class="d-flex flex-wrap gap-3 align-items-center">
        <div class="d-flex align-items-center">
            <div style="width: 20px; height: 20px; background-color: #4dabf7; border-radius: 4px; margin-right: 8px;"></div>
            <span>Scholarships</span>
        </div>
        <div class="d-flex align-items-center">
            <div style="width: 20px; height: 20px; background-color: #f9c74f; border-radius: 4px; margin-right: 8px;"></div>
            <span>Events</span>
        </div>
    </div>
</div>
<!-- Legend End -->

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
        <div id="calendar"></div>
    </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
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

<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
