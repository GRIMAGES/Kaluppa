<?php
require_once '../../Backend/connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch notifications for the logged-in user categorized as "application"
$query = "SELECT * FROM notifications WHERE email = ? AND category = 'application' ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/user_css/user_notifications.css">
</head>
<body style="background-color: #ddead1;">
<?php include 'sidebar.php'; ?>
<?php include 'topbar.php'; ?>
<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Frontend/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <h2 class="mb-4">Notifications</h2>
    <div class="list-group">
        <?php if (!empty($notifications)): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $notification['status'] == 'unread' ? 'list-group-item-info' : ''; ?>" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                    <span><?php echo htmlspecialchars($notification['message']); ?></span>
                    <small class="text-muted"><?php echo htmlspecialchars($notification['created_at']); ?></small>
                    <button type="button" class="btn-close" aria-label="Close" onclick="event.stopPropagation(); closeNotification(<?php echo $notification['id']; ?>);"></button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="list-group-item">No notifications found.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function markAsRead(notificationId) {
        fetch(`../../Backend/user controller/mark_notification_read.php?id=${notificationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload the page to update the notification status
                } else {
                    console.error('Error marking notification as read:', data.message);
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
    }

    function closeNotification(notificationId) {
        fetch(`../../Backend/user controller/close_notification.php?id=${notificationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload the page to update the notification status
                } else {
                    console.error('Error closing notification:', data.message);
                }
            })
            .catch(error => {
                console.error('Error closing notification:', error);
            });
    }
</script>
</body>
</html>
