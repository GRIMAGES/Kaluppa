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
        die("Error: No students selected for enrollment.");
    }

    $selected_students = $_POST['selected_students'];

    foreach ($selected_students as $student_id) {
        $student_id = $conn->real_escape_string($student_id);

        // Process enrollment logic (e.g., update status to "Enrolled")
        $stmt = $conn->prepare("UPDATE applications SET status = 'Enrolled' WHERE id = ?");
        if (!$stmt) {
            die("SQL error in preparation: " . $conn->error);
        }

        $stmt->bind_param("s", $student_id); // Treat student_id as a string
        if (!$stmt->execute()) {
            die("SQL error during execution: " . $stmt->error);
        }
        $stmt->close();
    }

    header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?enrollment_success=1");
    exit();
} else {
    header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php?error=invalid_request");
    exit();
}
?>
