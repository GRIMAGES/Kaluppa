<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php'; // Include log_helper.php

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch user details, including profile picture
$query = "SELECT first_name, middle_name, last_name, profile_picture FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($first_name, $middle_name, $last_name, $profile_picture);

if ($stmt->fetch()) {
    // Construct full name
    $fullName = trim("$first_name $middle_name $last_name");

    $profilePic = !empty($profile_picture) 
        ? "/Frontend/admin_dashboard/uploads/profile_pics/" . $profile_picture 
        : "/Frontend/assets/default-profile.png";

    // Debugging: Log profile picture file existence
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $profilePic;
    if (!file_exists($fullPath)) {
        error_log("Profile picture not found at: " . $fullPath);
        $profilePic = "/Frontend/assets/default-profile.png";
    } else {
        error_log("Profile picture found: " . $fullPath);
    }

    insertLog($email, 'View', 'User accessed the sidebar', 'info'); // Log user action
}

$stmt->close();

// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

$navLinks = [
    ['href' => 'user_dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
    ['href' => 'user_alumni.php', 'icon' => 'fas fa-user-graduate', 'text' => 'Alumni'],
    ['href' => 'user_courses.php', 'icon' => 'fas fa-graduation-cap', 'text' => 'Courses'],
    ['href' => 'user_work.php', 'icon' => 'fas fa-hands-helping', 'text' => 'Volunteer Work'],
    ['href' => 'user_events.php', 'icon' => 'fas fa-calendar-alt', 'text' => 'Events'],
    ['href' => 'user_settings.php', 'icon' => 'fas fa-cogs', 'text' => 'Settings'],
    ['href' => 'user_transactions.php', 'icon' => 'fas fa-exchange-alt', 'text' => 'Applications'],
    ['href' => 'user_calendar.php', 'icon' => 'fas fa-calendar', 'text' => 'Calendar'],
    ['href' => 'user_notifications.php', 'icon' => 'fas fa-bell', 'text' => 'Notifications'],
    
];
?>

<link rel="stylesheet" href="../CSS/user_css/sidebar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<button class="burger-button d-block d-md-none" onclick="toggleSidebar()">☰</button>

<div class="sidebar">
    <div class="profile-section text-center">
        <img src="/Kaluppa/Frontend/admin_dashboard/uploads/profile_pics/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-pic mb-2" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
        <p class="profile-name font-bold"><?php echo htmlspecialchars($fullName ?: 'John Doe'); ?></p>
    </div>
    <nav class="nav-menu mt-4">
        <?php foreach ($navLinks as $link): ?>
            <a class="nav-link <?php echo ($current_page == $link['href']) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($link['href']); ?>">
                <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i> <span><?php echo htmlspecialchars($link['text']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <button class="logout-btn" style="background: none; border: none; color: inherit;" data-bs-toggle="modal" data-bs-target="#logoutModal">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </button>
</div>

<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('open');
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>