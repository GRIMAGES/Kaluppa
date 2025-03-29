<?php
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $userId = $_POST['id'];

    $query = "UPDATE user SET failed_attempts = 0, locked_until = NULL WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo "Account unlocked successfully.";
    } else {
        echo "Error unlocking account.";
    }
} else {
    echo "Invalid request.";
}
?>
