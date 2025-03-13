<?php
require_once '../../Backend/connection.php';

if (isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $course = $result->fetch_assoc();
        echo json_encode($course);
    } else {
        echo json_encode(["error" => "Course not found."]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Invalid course ID."]);
}

$conn->close();
?>
