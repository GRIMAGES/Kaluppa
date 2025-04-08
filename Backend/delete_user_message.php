<?php
require_once 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$messageId = $_POST['message_id'] ?? 0;
$email = $_SESSION['email'];

if ($messageId == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
    exit();
}

// Fetch user ID
$stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}
$user = $result->fetch_assoc();
$userId = $user['id'];

// Check if the message belongs to the user and is not from admin
$checkStmt = $conn->prepare("SELECT id FROM chat_messages WHERE id = ? AND user_id = ? AND sender = 'Alumni'");
$checkStmt->bind_param("ii", $messageId, $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You can only delete your own messages']);
    exit();
}

// Delete the message with the given ID
$deleteStmt = $conn->prepare("DELETE FROM chat_messages WHERE id = ?");
$deleteStmt->bind_param("i", $messageId);
if ($deleteStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
}
?> 