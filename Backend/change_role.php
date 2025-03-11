<?php
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['id'];
    $newRole = $_POST['role'];

    $stmt = $conn->prepare("UPDATE user SET role = ? WHERE id = ?");
    $stmt->bind_param('si', $newRole, $userId);

    if ($stmt->execute()) {
        echo 'Role updated successfully';
    } else {
        echo 'Error updating role';
    }

    $stmt->close();
    $conn->close();
}
?>
