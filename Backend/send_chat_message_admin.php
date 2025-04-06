<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Admin/login.php");
    exit();
}

$userId = $_POST['user_id'] ?? 0;
$message = $_POST['message'] ?? '';

if (empty($message) || $userId == 0) {
    header("Location: /Admin/chat_admin.php?error=Invalid input");
    exit();
}

// Insert chat message
$insertStmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender, text) VALUES (?, 'Admin', ?)");
$insertStmt->bind_param("is", $userId, $message);
if ($insertStmt->execute()) {
    header("Location: /Admin/chat_admin.php?success=Message sent");
} else {
    header("Location: /Admin/chat_admin.php?error=Failed to send message");
}
?>
