<?php
require_once 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $inquiry_type = $_POST['inquiry_type'] ?? null;

    if ($user_id && $inquiry_type) {
        // Prepare the SQL statement to delete all messages for the user and inquiry type
        $stmt = $conn->prepare("DELETE FROM chat_messages WHERE user_id = ? AND inquiry_type = ?");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $inquiry_type);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to execute delete statement.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare delete statement.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID or inquiry type.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?> 