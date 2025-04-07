<?php
require_once 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_GET['user_id'] ?? 0;

if ($userId == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Check for existing conversations
$stmt = $conn->prepare("SELECT DISTINCT inquiry_type FROM chat_messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'inquiry_type' => $row['inquiry_type']]);
} else {
    echo json_encode(['success' => false, 'message' => 'No existing conversations']);
}
?> 