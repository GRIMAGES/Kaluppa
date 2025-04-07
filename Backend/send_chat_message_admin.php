<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$userId = $_POST['user_id'] ?? 0;
$message = $_POST['message'] ?? '';

if (empty($message) || $userId == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Insert chat message
$insertStmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender, text) VALUES (?, 'Admin', ?)");
$insertStmt->bind_param("is", $userId, $message);

if ($insertStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
?>
