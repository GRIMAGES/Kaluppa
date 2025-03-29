<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    if (!$stmt) {
        die("SQL error in preparation: " . $conn->error);
    }

    $stmt->bind_param("ss", $new_status, $application_id); // Use "ss" since application_id is a string
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
