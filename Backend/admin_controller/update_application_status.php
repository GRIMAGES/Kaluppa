<?php
session_start();
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'], $_POST['status'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['status'];

    // Update the status of the application
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    if (!$stmt) {
        die("SQL error in preparation: " . $conn->error);
    }

    $stmt->bind_param("ss", $new_status, $application_id); // Treat both as strings
    if ($stmt->execute()) {
        $stmt->close();
        $_SESSION['status_updated'] = true;
        header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?status_updated=1");
        exit();
    } else {
        die("SQL error during execution: " . $stmt->error);
    }
} else {
    die("Invalid request.");
}
?>
