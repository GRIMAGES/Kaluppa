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

$userId = $_GET['user_id'] ?? 0;

if ($userId == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Fetch chat messages for this user
$stmt = $conn->prepare("
    SELECT c.sender, c.text, c.created_at
    FROM chat_messages c
    WHERE c.user_id = ?
    ORDER BY c.created_at ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(['success' => true, 'messages' => $messages]);
?>
