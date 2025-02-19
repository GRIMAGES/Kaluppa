<?php
require_once '../../Backend/connection.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin css/events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
   <!-- Sidebar -->
<div class="sidebar p-3">
    <div class="text-center mb-4">
        <img src="profile-pic.jpg" alt="Profile" class="mb-2">
        <h5>Admin Name</h5>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="admin_dashboard.php" class="nav-link active"><i class="fas fa-home me-2"></i> Dashboard</a>
        </li>
        <li class="nav-item">
        <a href="admin_users.php" class="nav-link"><i class="fas fa-users me-2"></i> Users</a>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#contentManager"><i class="fas fa-cogs me-2"></i> Content Manager</a>
            <div class="collapse" id="contentManager">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a href="admin_events.php" class="nav-link text-light"><i class="fas fa-calendar me-2"></i> Events</a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_courses.php" class="nav-link text-light"><i class="fas fa-book me-2"></i> Courses</a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a href="#" class="nav-link" data-bs-toggle="collapse" data-bs-target="#applicationManager"><i class="fas fa-file-alt me-2"></i> Application Manager</a>
            <div class="collapse" id="applicationManager">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a href="admin_scholarship.php" class="nav-link text-light"><i class="fas fa-graduation-cap me-2"></i> Scholarship</a>
                    </li>
                    <li class="nav-item">
                        <a href="admin_volunteer.php" class="nav-link text-light"><i class="fas fa-handshake me-2"></i> Volunteer</a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
    <button class="btn btn-danger mt-auto w-100" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
</div>


    <!-- Main Content -->
    <div class="content" style="margin-left: 250px; padding: 20px;">
       
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
