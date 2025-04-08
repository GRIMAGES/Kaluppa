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

$userId = $_GET['user_id'] ?? 0;
$inquiryType = $_GET['inquiry_type'] ?? '';

if ($userId == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

try {
    // Fetch chat messages for this user
    $stmt = $conn->prepare("
        SELECT c.id, c.sender, c.text, c.created_at
        FROM chat_messages c
        WHERE c.user_id = ?
        " . (!empty($inquiryType) ? "AND c.inquiry_type = ?" : "") . "
        ORDER BY c.created_at ASC
    ");

    if (!empty($inquiryType)) {
        $stmt->bind_param("is", $userId, $inquiryType);
    } else {
        $stmt->bind_param("i", $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
