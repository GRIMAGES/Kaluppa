<?php
ini_set('display_errors', 0); // Disable error display to prevent HTML output
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require_once 'connection.php';
session_start();

// Set header to indicate JSON response
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$email = $_SESSION['email'];
$message = $_POST['message'] ?? '';
$inquiryType = $_POST['inquiry_type'] ?? '';

if (empty($message) || empty($inquiryType)) {
    echo json_encode(['success' => false, 'message' => 'Message and inquiry type cannot be empty']);
    exit();
}

try {
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

    // Insert chat message with inquiry type
    $insertStmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender, text, inquiry_type) VALUES (?, 'Alumni', ?, ?)");
    $insertStmt->bind_param("iss", $userId, $message, $inquiryType);
    if ($insertStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>