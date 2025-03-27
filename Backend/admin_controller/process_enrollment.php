<?php
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_students'])) {
    $selected_students = $_POST['selected_students'];
    $course_id = intval($_POST['course_id']); // Ensure course_id is passed in the form

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
        if ($enrolled_count < $capacity) {
            // Enroll the student
            $stmt = $conn->prepare("UPDATE applications SET status = 'Enrolled' WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $stmt->close();
            $enrolled_count++;
        } else {
            // Move to waitlist
            $stmt = $conn->prepare("UPDATE applications SET status = 'Waitlist' WHERE id = ?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Redirect back with a success message
    header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?enrollment_success=1");
    exit();
} else {
    die("No students selected or invalid request.");
}
?>
