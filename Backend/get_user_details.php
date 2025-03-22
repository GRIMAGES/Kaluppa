<?php
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['id'];

    // Debugging statements to log received POST data
    error_log("Received POST data: id=$userId");

    $stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name FROM user WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Debugging statement to log the SQL query and parameters
    error_log("SQL Query: SELECT id, first_name, middle_name, last_name FROM user WHERE id = ?");
    error_log("Parameters: id=$userId");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode($user);
        // Debugging statement to log fetched user details
        error_log("Fetched user details: " . json_encode($user));
    } else {
        echo 'Error fetching user details';
        // Debugging statement to log error
        error_log("Error: No user found with id=$userId");
    }
}
?>
