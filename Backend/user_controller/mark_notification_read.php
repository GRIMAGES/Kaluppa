<?php
require_once '../connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit();
}

$notification_id = intval($_GET['id']);
$email = $_SESSION['email'];

// Update notification status to 'read'
$query = "UPDATE notifications SET status = 'read' WHERE id = ? AND email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $notification_id, $email);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}

$stmt->close();
$conn->close();
?>
