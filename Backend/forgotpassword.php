<?php
session_start();
require_once 'connection.php';

// Ensure that the request method is POST and the session ID is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['id'])) {
    $userId = $_SESSION['id']; // Retrieve the user ID from the session
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Check if new password and confirmation match
    if ($newPassword !== $confirmPassword) {
        echo json_encode([
            'success' => false,
            'message' => 'Passwords do not match. Please try again.'
        ]);
        exit;
    }

    // Validate password criteria
    if (strlen($newPassword) < 12 || strlen($newPassword) > 16 || 
        !preg_match('/[A-Z]/', $newPassword) || 
        !preg_match('/[a-z]/', $newPassword) || 
        !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
        
        echo json_encode([
            'success' => false,
            'message' => 'Password does not meet the required criteria.'
        ]);
        exit;
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Prepare the SQL query to update the password
    $stmt = $conn->prepare("UPDATE user SET password = ?, reset_token = NULL WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);

    if ($stmt->execute()) {
        // Password change successful
        echo json_encode([
            'success' => true,
            'message' => 'Your password has been successfully changed.'
        ]);
    } else {
        // Error occurred during update
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again later.'
        ]);
    }
    $stmt->close();
} else {
    // If request is invalid or session ID is not set
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Please submit the change password form.'
    ]);
}
