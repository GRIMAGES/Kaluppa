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
    if (!isset($_POST['application_id']) || empty($_POST['application_id']) || !isset($_POST['application_type']) || empty($_POST['application_type'])) {
        die("Application ID and type are required.");
    }

    $application_id = $_POST['application_id'];
    $application_type = $_POST['application_type'];
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

    // Determine the table to delete from based on application type
    if ($application_type === 'Application') {
        $query = "DELETE FROM applications WHERE id = ? AND email = ?";
    } elseif ($application_type === 'Volunteer') {
        $query = "DELETE FROM volunteer_application WHERE id = ? AND email = ?";
    } else {
        die("Invalid application type.");
    }

    // Execute the delete query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("is", $application_id, $email);
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }

    // Log the deletion
    insertLog($user_id, 'Delete', "Deleted application with ID $application_id of type $application_type", 'info');

    $stmt->close();

    // Redirect with success message
    $_SESSION['success_message'] = "Application deleted successfully.";
    header("Location: /Kaluppa/Frontend/user_dashboard/user_transactions.php");
    exit();
} else {
    die("Invalid request method.");
}
?>
