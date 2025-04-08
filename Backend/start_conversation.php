<?php
require_once 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$email = $_SESSION['email'];
$inquiryType = $_POST['inquiry_type'] ?? '';

if (empty($inquiryType)) {
    echo json_encode(['success' => false, 'message' => 'Inquiry type cannot be empty']);
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

// Insert new conversation
$insertStmt = $conn->prepare("INSERT INTO conversations (user_id, inquiry_type) VALUES (?, ?)");
$insertStmt->bind_param("is", $userId, $inquiryType);
if ($insertStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to start conversation']);
}
?> 