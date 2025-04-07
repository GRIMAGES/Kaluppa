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

// Build correct file path for checking and displaying profile picture
$uploadDirRelative = '../../Frontend/admin_dashboard/uploads/profile_pics/';
$uploadDirAbsolute = realpath($uploadDirRelative);
$profilePicFilePath = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $profile_picture;

$profilePic = (!empty($profile_picture) && file_exists($profilePicFilePath))
    ? $uploadDirRelative . $profile_picture
    : '/Frontend/assets/default-profile.png';

// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Navigation links
$navLinks = [
    ['href' => 'admin_dashboard.php', 'icon' => 'fas fa-home', 'text' => 'Dashboard'],
    ['href' => 'admin_users.php', 'icon' => 'fas fa-users', 'text' => 'Users', 'role' => 'superadmin'],
    ['href' => '#', 'icon' => 'fas fa-cogs', 'text' => 'Content Manager', 'dropdown' => [
        ['href' => 'admin_events.php', 'icon' => 'fas fa-calendar', 'text' => 'Events'],
        ['href' => 'admin_works.php', 'icon' => 'fas fa-briefcase', 'text' => 'Works'],
        ['href' => 'admin_courses.php', 'icon' => 'fas fa-book', 'text' => 'Courses'],
        ['href' => 'admin_announcements.php', 'icon' => 'fas fa-bullhorn', 'text' => 'Announcements'],
        ['href' => 'featured_card.php', 'icon' => 'fas fa-star', 'text' => 'Featured Cards'],
    ]],
    ['href' => '#', 'icon' => 'fas fa-tasks', 'text' => 'Application Manager', 'dropdown' => [
        ['href' => 'admin_scholarship.php', 'icon' => 'fas fa-graduation-cap', 'text' => 'Scholarship'],
        ['href' => 'admin_volunteer.php', 'icon' => 'fas fa-handshake', 'text' => 'Volunteer'],
        ['href' => 'admin_alumni.php', 'icon' => 'fas fa-user-graduate', 'text' => 'Alumni'],
    ]],
    ['href' => 'admin_analytics.php', 'icon' => 'fas fa-chart-line', 'text' => 'Analytics'],
    ['href' => 'reports.php', 'icon' => 'fas fa-chart-bar', 'text' => 'Reports'],
    ['href' => 'admin_settings.php', 'icon' => 'fas fa-cog', 'text' => 'Admin Settings'],
    ['href' => 'admin_logs.php', 'icon' => 'fas fa-file-alt', 'text' => 'Admin Logs', 'role' => 'superadmin'],
    ['href' => 'chat_admin.php', 'icon' => 'fas fa-comments', 'text' => 'Chat Admin'],
    ['href' => 'admin_backup.php', 'icon' => 'fas fa-database', 'text' => 'Admin Backup'],
];
?>

<!-- Sidebar Styles and Icons -->
<link rel="stylesheet" href="../CSS/admin_css/sidebar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<!-- Burger Menu Button (Mobile) -->
<button class="burger-button d-block d-md-none" onclick="toggleSidebar()">☰</button>

<!-- Sidebar Container -->
<div class="sidebar p-3 d-flex flex-column">
    <div class="text-center mb-3">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="mb-2 rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
        <h5 class="mb-0"><?php echo htmlspecialchars($adminName); ?></h5>
    </div>

    <ul class="nav flex-column flex-grow-1">
        <?php foreach ($navLinks as $link): ?>
            <?php if (!isset($link['role']) || $link['role'] === $role): ?>
                <?php if (isset($link['dropdown'])): ?>
                    <li class="nav-item">
                        <a class="nav-link small" href="#" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo str_replace(' ', '', $link['text']); ?>" aria-expanded="false">
                            <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i> <span class="nav-text"><?php echo htmlspecialchars($link['text']); ?></span>
                        </a>
                        <div class="collapse <?php echo in_array($current_page, array_column($link['dropdown'], 'href')) ? 'show' : ''; ?>" id="collapse<?php echo str_replace(' ', '', $link['text']); ?>">
                            <ul class="nav flex-column ms-3">
                                <?php foreach ($link['dropdown'] as $sublink): ?>
                                    <li class="nav-item">
                                        <a class="nav-link small <?php echo ($current_page === $sublink['href']) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($sublink['href']); ?>">
                                            <i class="<?php echo htmlspecialchars($sublink['icon']); ?>"></i> <span class="nav-text"><?php echo htmlspecialchars($sublink['text']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link small <?php echo ($current_page === $link['href']) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($link['href']); ?>">
                            <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i> <span class="nav-text"><?php echo htmlspecialchars($link['text']); ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <!-- Logout Button -->
    <button class="btn btn-danger mt-auto w-100" data-bs-toggle="modal" data-bs-target="#logoutModal">Logout</button>
</div>

<!-- JavaScript for Sidebar and Collapse -->
<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('open');
    }

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

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<style>
    .nav-text {
        font-size: 0.875rem;
    }
</style>
