// Example: Display current date and time
window.onload = function() {
    const dateTimeBanner = document.querySelector('.date-time-banner');
    setInterval(() => {
        const now = new Date();
        dateTimeBanner.textContent = now.toLocaleString('en-US', {
            dateStyle: 'full',
            timeStyle: 'short'
        });
    }, 1000);
};
document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: '/path/to/your/events.php', // Replace with your PHP file for event data
        editable: false,
        locale: 'en', // Adjust locale as needed
    });
    calendar.render();
});