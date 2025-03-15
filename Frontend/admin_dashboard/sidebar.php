<?php
require_once '../../Backend/connection.php';

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch admin details from the user table
$query = "SELECT first_name, middle_name, last_name, profile_picture, role FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($first_name, $middle_name, $last_name, $profile_picture, $role);
$stmt->fetch();
$stmt->close();

// Construct full name
$adminName = trim("$first_name $middle_name $last_name");

// Set profile picture path (use a default if empty)
$profilePic = !empty($profile_picture) ? "/KALUPPA/Frontend/admin_dashboard/uploads/profile_pics/" . $profile_picture : "/Frontend/assets/default-profile.png";

// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

$navLinks = [
    ['href' => 'admin_dashboard.php', 'icon' => 'fas fa-home', 'text' => 'Dashboard'],
    ['href' => 'admin_users.php', 'icon' => 'fas fa-users', 'text' => 'Users', 'role' => 'superadmin'],
    ['href' => '#', 'icon' => 'fas fa-cogs', 'text' => 'Content Manager', 'dropdown' => [
        ['href' => 'admin_events.php', 'icon' => 'fas fa-calendar', 'text' => 'Events'],
        ['href' => 'admin_works.php', 'icon' => 'fas fa-briefcase', 'text' => 'Works'],
        ['href' => 'admin_courses.php', 'icon' => 'fas fa-book', 'text' => 'Courses'],
        ['href' => 'admin_announcements.php', 'icon' => 'fas fa-bullhorn', 'text' => 'Announcements'],
        ['href' => 'featured_card.php', 'icon' => 'fas fa-star', 'text' => 'Featured Cards'], // New link
    ]],
    ['href' => '#', 'icon' => 'fas fa-tasks', 'text' => 'Application Manager', 'dropdown' => [ // New dropdown
        ['href' => 'admin_scholarship.php', 'icon' => 'fas fa-graduation-cap', 'text' => 'Scholarship'],
        ['href' => 'admin_volunteer.php', 'icon' => 'fas fa-handshake', 'text' => 'Volunteer'],
    ]],
    ['href' => 'admin_analytics.php', 'icon' => 'fas fa-chart-line', 'text' => 'Analytics'],
    ['href' => 'reports.php', 'icon' => 'fas fa-chart-bar', 'text' => 'Reports'],
    ['href' => 'admin_settings.php', 'icon' => 'fas fa-cog', 'text' => 'Admin Settings'],
    ['href' => '#', 'icon' => 'fas fa-certificate', 'text' => 'Certificates', 'dropdown' => [
        ['href' => 'admin_certificate.php?type=scholarship', 'icon' => 'fas fa-graduation-cap', 'text' => 'Scholarship']
    ]],
];
?>

<link rel="stylesheet" href="../CSS/admin_css/sidebar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<button class="burger-button d-block d-md-none" onclick="toggleSidebar()">☰</button>

<div class="sidebar p-3 d-flex flex-column">
    <div class="text-center mb-3">
        <img src="../../Frontend/admin_dashboard/uploads/profile_pics/<?php echo htmlspecialchars($admin['profile_picture']); ?>" alt="Profile" class="mb-2 rounded-circle" style="width: 60px; height: 60px;">
        <h5><?php echo htmlspecialchars($adminName); ?></h5>
    </div>
    <ul class="nav flex-column flex-grow-1">
        <?php foreach ($navLinks as $link): ?>
            <?php if (!isset($link['role']) || $link['role'] == $role): ?>
                <?php if (isset($link['dropdown'])): ?>
                    <li class="nav-item">
                        <a class="nav-link small" href="#" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo str_replace(' ', '', $link['text']); ?>">
                            <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i> <span class="nav-text"><?php echo htmlspecialchars($link['text']); ?></span>
                        </a>
                        <div class="collapse <?php echo in_array($current_page, array_column($link['dropdown'], 'href')) ? 'show' : ''; ?>" id="collapse<?php echo str_replace(' ', '', $link['text']); ?>">
                            <ul class="nav flex-column ms-3">
                                <?php foreach ($link['dropdown'] as $sublink): ?>
                                    <li class="nav-item">
                                        <a class="nav-link small <?php echo ($current_page == $sublink['href']) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($sublink['href']); ?>">
                                            <i class="<?php echo htmlspecialchars($sublink['icon']); ?>"></i> <span class="nav-text"><?php echo htmlspecialchars($sublink['text']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link small <?php echo ($current_page == $link['href']) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($link['href']); ?>">
                            <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i> <span class="nav-text"><?php echo htmlspecialchars($link['text']); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
    <button class="btn btn-danger mt-auto w-100" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
</div>

<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('open');
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var collapseElements = document.querySelectorAll('.collapse');
        collapseElements.forEach(function (collapseElement) {
            collapseElement.addEventListener('show.bs.collapse', function () {
                var openCollapseElements = document.querySelectorAll('.collapse.show');
                openCollapseElements.forEach(function (openCollapseElement) {
                    if (openCollapseElement !== collapseElement) {
                        var collapseInstance = bootstrap.Collapse.getInstance(openCollapseElement);
                        collapseInstance.hide();
                    }
                });
            });
        });
    });
</script>
<style>
    .nav-text {
        font-size: 0.875rem; /* Smaller font size */
    }
</style>
