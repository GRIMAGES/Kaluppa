<?php
require_once 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = $_POST['message_id'] ?? null;

    if ($message_id) {
        // Prepare the SQL statement to delete the message
        $stmt = $conn->prepare("DELETE FROM chat_messages WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $message_id);
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
        echo json_encode(['success' => false, 'message' => 'Invalid message ID.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?> 