<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../connection.php';

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['selected_students']) || empty($_POST['selected_students'])) {
        header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?error=no_students_selected");
        exit();
    }

    $selected_students = $_POST['selected_students'];

    $stmt = $conn->prepare("SELECT course_id FROM applications WHERE id = ?");
    $stmt->bind_param("i", $selected_students[0]);
    $stmt->execute();
    $stmt->bind_result($course_id);
    if (!$stmt->fetch()) {
        $stmt->close();
        die("Error: course_id not found for the selected student.");
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT capacity FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $stmt->bind_result($capacity);
    if (!$stmt->fetch()) {
        $stmt->close();
        die("Invalid course or capacity not found.");
    }
    $stmt->close();

    $enrolled_count = 0;
    foreach ($selected_students as $student_id) {
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $status = ($enrolled_count < $capacity) ? 'Enrolled' : 'Waitlist';
        $stmt->bind_param("si", $status, $student_id);
        $stmt->execute();
        $stmt->close();
        $enrolled_count++;
    }

    header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?enrollment_success=1");
    exit();
} else {
    die("Invalid request method.");
}
?>
