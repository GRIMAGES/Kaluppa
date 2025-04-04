<?php
require_once '../../Backend/connection.php';
require_once '../../Backend/log_helper.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    if (!isset($_POST['application_id']) || empty($_POST['application_id'])) {
        die("Application ID is required.");
    }

    $application_id = $_POST['application_id'];
    $email = $_SESSION['email'];

    // Fetch user ID
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }
    $stmt->bind_result($user_id);
    if (!$stmt->fetch()) {
        $stmt->close();
        die("User not found.");
    }
    $stmt->close();

    // Delete application
    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ? AND email = ?");
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("is", $application_id, $email);
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }

    // Log the deletion
    insertLog($user_id, 'Delete', "Deleted application with ID $application_id", 'info');

    $stmt->close();

    // Redirect with success message
    $_SESSION['success_message'] = "Application deleted successfully.";
    header("Location: /Kaluppa/Frontend/user_dashboard/user_transactions.php");
    exit();
} else {
    die("Invalid request method.");
}
?>
