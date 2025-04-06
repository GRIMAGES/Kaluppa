<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../Backend/connection.php';

if (isset($_GET['application_id'])) {
    $applicationId = $_GET['application_id'];

    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->bind_param('s', $applicationId);

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