<?php
require_once '../../Backend/connection.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch user details, including profile picture
$query = "SELECT first_name, middle_name, last_name, profile_picture FROM user WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($first_name, $middle_name, $last_name, $profile_picture);
$stmt->fetch();
$stmt->close();

// Construct full name
$fullName = trim("$first_name $middle_name $last_name");

// Set profile picture path (use a default if empty)
$profilePic = !empty($profile_picture) ? "/Frontend/admin dashboard/uploads/profile_pics/" . $profile_picture : "/Frontend/assets/default-profile.png";

$navLinks = [
    ['href' => 'user_dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
    ['href' => 'user_courses.php', 'icon' => 'fas fa-graduation-cap', 'text' => 'Courses'],
    ['href' => 'user_work.php', 'icon' => 'fas fa-hands-helping', 'text' => 'Volunteer Work'],
    ['href' => 'user_events.php', 'icon' => 'fas fa-calendar-alt', 'text' => 'Events'],
    ['href' => 'user_settings.php', 'icon' => 'fas fa-cogs', 'text' => 'Settings'],
];
?>

<div class="sidebar">
    <div class="profile-section text-center">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile" class="profile-pic mb-2" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
        <p class="profile-name font-bold"><?php echo htmlspecialchars($fullName ?: 'John Doe'); ?></p>
    </div>
    <nav class="nav-menu mt-4">
        <?php foreach ($navLinks as $link): ?>
            <a class="nav-link" href="<?php echo htmlspecialchars($link['href']); ?>">
                <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i> <?php echo htmlspecialchars($link['text']); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <button class="logout-btn" style="background: none; border: none; color: inherit;" data-bs-toggle="modal" data-bs-target="#logoutModal">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</div>
