<?php
require_once '../connection.php';
require_once '../log_helper.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    $email = $_SESSION['email'];

    // Verify the application belongs to the logged-in user
    $stmt = $conn->prepare("SELECT id FROM applications WHERE id = ? AND email = ?");
    $stmt->bind_param("is", $application_id, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();

        // Delete the application
        $delete_stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
        $delete_stmt->bind_param("i", $application_id);
        if ($delete_stmt->execute()) {
            // Log the deletion
            $user_id = $_SESSION['user_id']; // Assuming user_id is stored in session
            insertLog($user_id, 'Delete', "Deleted application ID $application_id", 'warning');
            $_SESSION['success_message'] = "Application deleted successfully.";
        } else {
            $_SESSION['success_message'] = "Failed to delete the application.";
        }
        $delete_stmt->close();
    } else {
        $_SESSION['success_message'] = "Invalid application ID.";
    }
    $stmt->close();
}

header("Location: /Kaluppa/Frontend/user_dashboard/user_transactions.php");
exit();
