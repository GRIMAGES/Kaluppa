<?php
require_once '../../Backend/connection.php';

if (isset($_GET['application_id'])) {
    $applicationId = $_GET['application_id'];

    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->bind_param('i', $applicationId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete application.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?> 