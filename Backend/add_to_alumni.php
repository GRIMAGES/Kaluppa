<?php
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'];
    $lastName = $_POST['last_name'];

    $stmt = $conn->prepare("INSERT INTO alumni (user_id, first_name, middle_name, last_name, category, details, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $category = 'Course/Volunteer'; // Example category, adjust as needed
    $details = 'Details about the course or volunteer work'; // Example details, adjust as needed
    $status = 'completed'; // Example status, adjust as needed
    $stmt->bind_param("issssss", $userId, $firstName, $middleName, $lastName, $category, $details, $status);

    if ($stmt->execute()) {
        echo 'User added to alumni table successfully';
    } else {
        echo 'Error adding user to alumni table';
    }
}
?>
