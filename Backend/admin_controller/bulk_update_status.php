<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['selected_students'], $_POST['bulk_status'])) {
        $selectedStudents = $_POST['selected_students'];
        $bulkStatus = $_POST['bulk_status'];

        foreach ($selectedStudents as $studentId) {
            // Get the course ID for the student
            $query = "SELECT course_id FROM applications WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $studentId);
            $stmt->execute();
            $stmt->bind_result($courseId);
            $stmt->fetch();
            $stmt->close();

            // Check the number of enrolled students for the course
            $capacityQuery = "SELECT COUNT(*) AS enrolled_count FROM applications WHERE course_id = ? AND status = 'Enrolled'";
            $capacityStmt = $conn->prepare($capacityQuery);
            $capacityStmt->bind_param('i', $courseId);
            $capacityStmt->execute();
            $capacityStmt->bind_result($enrolledCount);
            $capacityStmt->fetch();
            $capacityStmt->close();

            // Assume course capacity is 5 (can be fetched dynamically if stored in the database)
            $courseCapacity = 5;

            // Automatically waitlist students if the course is full
            if ($bulkStatus === 'Enrolled' && $enrolledCount >= $courseCapacity) {
                $bulkStatus = 'Waitlisted';
            }

            // Update the student's status
            $updateQuery = "UPDATE applications SET status = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param('ss', $bulkStatus, $studentId);
            $updateStmt->execute();
            $updateStmt->close();
        }

        $_SESSION['message'] = "Status updated for selected students.";
    }
}
header("Location: ../../Frontend/admin_dashboard/admin_scholarship.php");
exit();
?>
