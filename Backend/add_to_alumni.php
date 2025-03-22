<?php
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'];
    $lastName = $_POST['last_name'];
    $category = $_POST['category'];
    $details = $_POST['details'];

    // Fetch course name or volunteer work title based on category
    if ($category == 'Course') {
        $stmt = $conn->prepare("SELECT name FROM courses WHERE user_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = $result->num_rows > 0 ? $result->fetch_assoc()['name'] : 'N/A';
    } else {
        $stmt = $conn->prepare("SELECT title FROM works WHERE user_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = $result->num_rows > 0 ? $result->fetch_assoc()['title'] : 'N/A';
    }

    // Debugging statement to check the value of details
    error_log("Details: " . $details);

    $stmt = $conn->prepare("INSERT INTO alumni (user_id, first_name, middle_name, last_name, category, details, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $status = 'completed'; // Example status, adjust as needed
    $stmt->bind_param("issssss", $userId, $firstName, $middleName, $lastName, $category, $details, $status);

    if ($stmt->execute()) {
        echo 'User added to alumni table successfully';
    } else {
        echo 'Error adding user to alumni table: ' . $stmt->error;
        // Debugging statement to check the error
        error_log("Error: " . $stmt->error);
    }
}
?>
