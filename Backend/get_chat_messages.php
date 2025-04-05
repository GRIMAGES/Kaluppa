<?php
require_once 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$email = $_SESSION['email'];

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

// Fetch chat messages
$chatStmt = $conn->prepare("SELECT sender, text FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
$chatStmt->bind_param("i", $userId);
$chatStmt->execute();
$chatResult = $chatStmt->get_result();

$messages = [];
while ($row = $chatResult->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(['success' => true, 'messages' => $messages]);
?>
