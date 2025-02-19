<!-- sidebar.php -->
<div class="sidebar p-3">
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
