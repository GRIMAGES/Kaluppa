<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (!isset($_POST['application_id']) || !isset($_POST['status'])) {
        die("Error: Missing application_id or status in POST data.");
    }

    $application_id = intval($_POST['application_id']);
    $new_status = $conn->real_escape_string($_POST['status']);

    // Debugging: Log received data
    error_log("Updating application ID: $application_id to status: $new_status");

    // Update the status in the database
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    if (!$stmt) {
        die("SQL error in preparation: " . $conn->error);
    }

    $stmt->bind_param("si", $new_status, $application_id);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?status_updated=1");
        exit();
    } else {
        die("SQL error during execution: " . $stmt->error);
    }
} else {
    header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?error=invalid_request");
    exit();
}
?>
