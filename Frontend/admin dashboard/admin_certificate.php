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

$adminEmail = $_SESSION['email'] ?? ''; // Handle undefined array key
// Fetch the admin's full name from the user table
$query = "SELECT CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS admin_name FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $adminEmail);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$adminName = $admin['admin_name'] ?? ''; // Handle undefined array key
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Certificate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin css/courses.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
 <!-- Sidebar -->
 <<div class="sidebar p-3">
        <div class="text-center mb-3">
            <img src="profile-pic.jpg" alt="Profile" class="mb-2 rounded-circle" style="width: 60px; height: 60px;">
            <h5><?php echo htmlspecialchars($adminName); ?></h5>
        </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="admin_dashboard.php" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a>
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
                        <a href="admin_works.php" class="nav-link text-light"><i class="fas fa-briefcase me-2"></i> Works</a>
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
        <li class="nav-item">
            <a href="admin_analytics.php" class="nav-link"><i class="fas fa-chart-line me-2"></i> Analytics</a>
        </li>
        <li class="nav-item">
            <a href="reports.php" class="nav-link active"><i class="fas fa-chart-bar me-2"></i> Reports</a>
        </li>
    </ul>
    <button class="btn btn-danger mt-auto w-100" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color: red;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="logoutForm" action="admin_scholarship.php" method="POST">
                    <button type="submit" name="logout" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="content">
    <div class="container">
        <h2>Generate Certificate</h2>
        <form action="/Backend/admin_controller/generate_certificate.php" method="POST">
            <div class="mb-3">
                <label for="studentName" class="form-label">Student Name</label>
                <input type="text" class="form-control" id="studentName" name="student_name" required>
            </div>
            <div class="mb-3">
                <label for="studentEmail" class="form-label">Student Email</label>
                <input type="email" class="form-control" id="studentEmail" name="student_email" required>
            </div>
            <button type="submit" class="btn btn-primary">Generate Certificate</button>
        </form>
    </div>
</div>

<!-- Script for Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
