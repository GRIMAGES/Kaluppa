<?php
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'];
    $lastName = $_POST['last_name'];
    $category = $_POST['category'];

    // Debugging statements to log received POST data
    error_log("Received POST data: user_id=$userId, first_name=$firstName, middle_name=$middleName, last_name=$lastName, category=$category");

    // Fetch course name or volunteer work title based on category
    if ($category == 'Course') {
        $stmt = $conn->prepare("SELECT c.name FROM courses c JOIN applications a ON c.id = a.course_id WHERE a.user_id = ? AND c.status = 'completed'");
        if (!$stmt) {
            error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            echo 'Error preparing statement: ' . $conn->error;
            exit();
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = $result->num_rows > 0 ? $result->fetch_assoc()['name'] : 'N/A';
        // Debugging statement to log course details
        error_log("Course details: " . $details);
    } else if ($category == 'Volunteer') {
        $stmt = $conn->prepare("SELECT w.title FROM works w JOIN volunteer_application va ON w.id = va.work_id WHERE va.user_id = ? AND w.status = 'completed'");
        if (!$stmt) {
            error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            echo 'Error preparing statement: ' . $conn->error;
            exit();
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = $result->num_rows > 0 ? $result->fetch_assoc()['title'] : 'N/A';
        // Debugging statement to log work details
        error_log("Work details: " . $details);
    } else {
        $details = 'N/A';
        error_log("Invalid category: " . $category);
    }

    // Debugging statement to check the value of details
    error_log("Details: " . $details);

    $stmt = $conn->prepare("INSERT INTO alumni (user_id, first_name, middle_name, last_name, category, details, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        echo 'Error preparing statement: ' . $conn->error;
        exit();
    }
    $status = 'completed'; // Example status, adjust as needed
    $stmt->bind_param("issssss", $userId, $firstName, $middleName, $lastName, $category, $details, $status);

    // Debugging statements to log the SQL query and parameters
    error_log("SQL Query: INSERT INTO alumni (user_id, first_name, middle_name, last_name, category, details, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    error_log("Parameters: user_id=$userId, first_name=$firstName, middle_name=$middleName, last_name=$lastName, category=$category, details=$details, status=$status");

    if ($stmt->execute()) {
        echo 'User added to alumni table successfully';
    } else {
        echo 'Error adding user to alumni table: ' . $stmt->error;
        // Debugging statement to check the error
        error_log("Error: " . $stmt->error);
    }
}
?>
