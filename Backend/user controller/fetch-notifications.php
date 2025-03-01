<?php
require_once '../connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$email = $_SESSION['email'];

// Fetch notifications for the logged-in user
$query = "SELECT * FROM notifications WHERE email = ? AND status = 'unread' ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

echo json_encode($notifications);
?>
