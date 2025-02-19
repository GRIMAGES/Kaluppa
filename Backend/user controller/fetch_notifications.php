<?php
// connection.php (Make sure your connection.php file is set up properly)
require_once '../../Backend/connection.php';
session_start();

// Function to fetch notifications for the logged-in user
function getNotifications($userId) {
    global $pdo;

    // Query to fetch notifications
    $query = "SELECT * FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch notifications for the logged-in user
$userId = $_SESSION['user_id']; // Assuming user ID is stored in session
$notifications = getNotifications($userId);

// Send the notifications as JSON for frontend use
echo json_encode($notifications);
?>
