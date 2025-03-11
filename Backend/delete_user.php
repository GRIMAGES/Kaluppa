<?php
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param('i', $userId);

    if ($stmt->execute()) {
        echo 'User deleted successfully';
    } else {
        echo 'Error deleting user';
    }

    $stmt->close();
    $conn->close();
}
?>
