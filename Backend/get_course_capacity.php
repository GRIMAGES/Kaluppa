<?php
require_once 'connection.php';

if (isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);

    $stmt = $conn->prepare("SELECT capacity FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $stmt->bind_result($capacity);
    $stmt->fetch();
    $stmt->close();

    echo $capacity !== null ? $capacity : 0; // Return capacity or 0 if not found
} else {
    echo 0; // Default to 0 if no course ID is provided
}
?>
