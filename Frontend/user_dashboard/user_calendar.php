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
        $endDate->modify('+1 day'); // Include the end date

        for ($date = $startDate; $date < $endDate; $date->modify('+1 day')) {
            $events[] = [
                'title' => $course['name'],
                'start' => $date->format('Y-m-d'),
                'description' => 'Scholarship: ' . $course['name'],
                'type' => 'scholarship'
            ];
        }
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
    <link rel="stylesheet" href="../CSS/user_css/user_calendar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .calendar-container {
            padding: 20px;
            animation: fadeIn 0.8s ease-in-out;
        }
        .fade-in {
            animation: fadeIn 0.7s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .event-course {
            background-color: #cce5ff;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.4s ease;
        }
        .event-regular {
            background-color: #d4edda;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.4s ease;
        }
        .fc .fc-event {
            transition: all 0.4s ease;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>

<!-- Calendar Section -->
<div class="calendar-container">
    <h3 class="mb-3">Event Calendar</h3>
    <div class="calendar-wrapper">
        <div id="calendar"></div>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-container mb-4 px-3">
    <label for="eventFilter" class="form-label">Filter by Type:</label>
    <select id="eventFilter" class="form-select">
        <option value="all">All</option>
        <option value="event">Events</option>
        <option value="scholarship">Scholarships</option>
    </select>

    <label for="timeFilter" class="form-label mt-2">Filter by Time:</label>
    <select id="timeFilter" class="form-select">
        <option value="all">All Times</option>
        <option value="morning">Morning (5 AM - 12 PM)</option>
        <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
        <option value="evening">Evening (5 PM - 9 PM)</option>
        <option value="night">Night (9 PM - 5 AM)</option>
    </select>
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
        const calendarEl = document.getElementById('calendar');
        const originalEvents = <?php echo json_encode($events); ?>;

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: originalEvents,
            editable: false,
            eventClick: function (info) {
                document.getElementById('eventTitle').innerText = info.event.title;
                document.getElementById('eventDescription').innerText = info.event.extendedProps.description;
                new bootstrap.Modal(document.getElementById('eventModal')).show();
            },
            eventContent: function (arg) {
                const type = arg.event.extendedProps.type;
                const cssClass = type === 'scholarship' ? 'event-course fade-in' : 'event-regular fade-in';
                return { html: `<div class="${cssClass}">${arg.event.title}</div>` };
            }
        });

        calendar.render();

        function filterEvents() {
            const type = document.getElementById('eventFilter').value;
            const time = document.getElementById('timeFilter').value;

            const filtered = originalEvents.filter(event => {
                const matchType = (type === 'all') || (event.type === type);
                const hour = new Date(event.start).getHours();
                let matchTime = true;
                if (time === 'morning') matchTime = hour >= 5 && hour < 12;
                else if (time === 'afternoon') matchTime = hour >= 12 && hour < 17;
                else if (time === 'evening') matchTime = hour >= 17 && hour < 21;
                else if (time === 'night') matchTime = hour >= 21 || hour < 5;
                return matchType && matchTime;
            });

            calendar.removeAllEvents();
            calendar.addEventSource(filtered);
        }

        document.getElementById('eventFilter').addEventListener('change', filterEvents);
        document.getElementById('timeFilter').addEventListener('change', filterEvents);
    });
</script>
</body>
</html>
