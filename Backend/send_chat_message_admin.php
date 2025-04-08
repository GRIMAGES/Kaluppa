<?php
ini_set('display_errors', 0); // Disable error display to prevent HTML output
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require_once 'connection.php';
session_start();

// Set header to indicate JSON response
header('Content-Type: application/json');

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

try {
    // Get the inquiry type for this user
    $inquiryStmt = $conn->prepare("SELECT inquiry_type FROM conversations WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $inquiryStmt->bind_param("i", $userId);
    $inquiryStmt->execute();
    $inquiryResult = $inquiryStmt->get_result();
    
    $inquiryType = '';
    if ($inquiryResult->num_rows > 0) {
        $inquiryRow = $inquiryResult->fetch_assoc();
        $inquiryType = $inquiryRow['inquiry_type'];
    }
    
    // Insert chat message with inquiry type
    $insertStmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender, text, inquiry_type) VALUES (?, 'Admin', ?, ?)");
    $insertStmt->bind_param("iss", $userId, $message, $inquiryType);

    if ($insertStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
