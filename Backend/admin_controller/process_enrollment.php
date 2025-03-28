<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../connection.php';

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_students'])) {
    $selected_students = $_POST['selected_students'];

    // Fetch the course_id from the applications table using the first selected student
    $first_student_id = $selected_students[0]; // Use the first student ID to determine the course
    $stmt = $conn->prepare("SELECT course_id FROM applications WHERE id = ?");
    $stmt->bind_param("s", $first_student_id); // Treat id as a string
    $stmt->execute();
    $stmt->bind_result($course_id);
    if (!$stmt->fetch()) { // Check if a result is fetched
        $stmt->close();
        die("Error: course_id not found for the selected student.");
    }
    $stmt->close();

    // Fetch the course capacity
    $stmt = $conn->prepare("SELECT capacity FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $stmt->bind_result($capacity);
    if (!$stmt->fetch()) { // Check if a result is fetched
        $stmt->close();
        die("Invalid course or capacity not found."); // Handle invalid course ID
    }
    $stmt->close();

    // Process enrollment
    $enrolled_count = 0;
    foreach ($selected_students as $student_id) {
        // Ensure $student_id is treated as a string if it contains non-numeric values
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        if ($enrolled_count < $capacity) {
            $status = 'Enrolled';
        } else {
            $status = 'Waitlist';
        }
        $stmt->bind_param("ss", $status, $student_id); // Use "ss" to bind both as strings
        $stmt->execute();
        $stmt->close();
        $enrolled_count++;
    }

    // Redirect back with a success message
    header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?enrollment_success=1");
    exit();
} else {
    die("No students selected or invalid request.");
}
?>
