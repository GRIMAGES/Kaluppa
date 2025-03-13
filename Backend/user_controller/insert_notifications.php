<?php
// connection.php (Make sure your connection.php file is set up properly)
require_once '../../Backend/connection.php';
session_start();

// Function to add a notification
function addNotification($userId, $message, $category) {
    global $pdo;

    // Insert query
    $query = "INSERT INTO notifications (user_id, message, category) 
              VALUES (:user_id, :message, :category)";
    $stmt = $pdo->prepare($query);

    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':category', $category);

    if ($stmt->execute()) {
        echo "Notification added successfully.";
    } else {
        echo "Failed to add notification.";
    }
}

// Example usage when a user submits a form (replace with actual logic)
if (isset($_POST['submit'])) {
    $userId = $_SESSION['user_id']; // Assuming user ID is stored in session
    $message = "Your application form has been successfully submitted.";
    $category = 'form_submission'; // Example category

    // Add notification
    addNotification($userId, $message, $category);
}
?>
