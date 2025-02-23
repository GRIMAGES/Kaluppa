<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<link rel="stylesheet" href="../CSS/user_css/topbar.css">

<!-- Top Header -->
<header class="top-header d-flex justify-content-between align-items-center p-2" style="background: #4b6043;">
    <h1 class="text-white font-bold mb-0">Kaluppa Foundation</h1>
    <div class="nav-icons d-flex align-items-center">
        <i class="fas fa-home text-white me-3"></i>

        <!-- Notification Icon -->
        <div class="notification-container position-relative">
            <div id="notification-icon" class="position-relative">
                <i class="fas fa-bell text-white me-3"></i>
                <span id="notification-count" class="badge bg-danger position-absolute top-0 start-100 translate-middle d-none">0</span>
                <?php 
                if (isset($_SESSION['notification'])): ?>
                    <span id="notification-message" class="badge bg-success text-white position-absolute top-0 end-0">
                        <?php 
                        echo htmlspecialchars($_SESSION['notification']); 
                        unset($_SESSION['notification']); // Clear the notification after displaying
                        ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="notification-dropdown bg-white shadow p-3 position-absolute d-none">
                <h6 class="fw-bold">Notifications</h6>
                <ul id="notification-list" class="list-unstyled mb-0"></ul>
            </div>
        </div>
    </div>
</header>

<!-- JavaScript -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const notificationIcon = document.getElementById("notification-icon");
    const notificationCount = document.getElementById("notification-count");
    const notificationList = document.getElementById("notification-list");
    const notificationDropdown = document.querySelector(".notification-dropdown");

    // Fetch notifications from PHP
    function fetchNotifications() {
        fetch('../../Backend/user controller/fetch-notifications.php')  // Adjust the path to your PHP file
            .then(response => response.json())
            .then(data => {
                updateNotifications(data);
            });
    }

    // Update notification UI
    function updateNotifications(notifications) {
        notificationList.innerHTML = "";
        notifications.forEach((notification) => {
            let li = document.createElement("li");
            li.textContent = notification.message;
            li.classList.add("border-bottom", "p-2");
            notificationList.appendChild(li);
        });

        notificationCount.textContent = notifications.length;
        notificationCount.classList.toggle("d-none", notifications.length === 0);
    }

    // Toggle notification dropdown
    notificationIcon.addEventListener("click", function (event) {
        event.stopPropagation();
        notificationDropdown.classList.toggle("show");
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function (event) {
        if (!notificationDropdown.contains(event.target) && !notificationIcon.contains(event.target)) {
            notificationDropdown.classList.remove("show");
        }
    });

    // Fetch notifications initially
    fetchNotifications();
});
</script>

<!-- CSS -->
<style>
.notification-container {
    cursor: pointer;
    position: relative;
}

.notification-container .badge {
    font-size: 0.8rem;
    padding: 3px 6px;
    border-radius: 50%;
}

.notification-dropdown {
    width: 250px;
    right: 0;
    top: 40px;
    z-index: 1000;
    border-radius: 5px;
    display: none;
    position: absolute;
    background: white;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    padding: 10px;
}

/* Ensure dropdown shows properly */
.notification-dropdown.show {
    display: block !important;
}

/* Notification List Styling */
.notification-dropdown h6 {
    margin-bottom: 10px;
    color: #007bff; /* Change this to your preferred color */
}

.notification-dropdown ul li {
    font-size: 0.9rem;
    padding: 5px;
    border-bottom: 1px solid #ddd;
}

.notification-dropdown ul li:last-child {
    border-bottom: none;
}
</style>

</body>
</html>
