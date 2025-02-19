<?php
require_once '../connection.php';
session_start();

// Check if course ID is provided
if (isset($_GET['id'])) {
    $course_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Fetch the course details from the database
    $sql = "SELECT * FROM courses WHERE id = '$course_id'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $course = mysqli_fetch_assoc($result);
    } else {
        echo "Course not found!";
        exit;
    }
}

// Handle updating the course
if (isset($_POST['update_course'])) {
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);
    $course_name = mysqli_real_escape_string($conn, $_POST['course_name']);
    $course_description = mysqli_real_escape_string($conn, $_POST['course_description']);
    $course_duration = mysqli_real_escape_string($conn, $_POST['course_duration']);
    $course_capacity = mysqli_real_escape_string($conn, $_POST['course_capacity']);
    $course_instructor = mysqli_real_escape_string($conn, $_POST['course_instructor']);
    $course_requisites = mysqli_real_escape_string($conn, $_POST['course_requisites']);

    if ($course_name && $course_description && $course_duration && $course_capacity && $course_instructor && $course_requisites) {
        $sql = "UPDATE courses 
                SET name='$course_name', description='$course_description', duration='$course_duration', 
                    capacity='$course_capacity', instructor='$course_instructor', requisites='$course_requisites' 
                WHERE id='$course_id'";

        echo $sql;  // Debugging: Output the query to make sure it's correct
        if (mysqli_query($conn, $sql)) {
            echo "Course updated successfully!";
            header("Location: course_management.php"); 
            exit;
        } else {
            echo "Error updating course: " . mysqli_error($conn);
        }
    } else {
        echo "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Edit Course</h1>
    <form method="POST" action="">
        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
        <input type="text" name="course_name" value="<?php echo htmlspecialchars($course['name']); ?>" placeholder="Course Name" required>
        <input type="text" name="course_description" value="<?php echo htmlspecialchars($course['description']); ?>" placeholder="Course Description" required>
        <input type="text" name="course_duration" value="<?php echo htmlspecialchars($course['duration']); ?>" placeholder="Duration" required>
        <input type="number" name="course_capacity" value="<?php echo htmlspecialchars($course['capacity']); ?>" placeholder="Capacity" required>
        <input type="text" name="course_instructor" value="<?php echo htmlspecialchars($course['instructor']); ?>" placeholder="Instructor" required>
        <input type="text" name="course_requisites" value="<?php echo htmlspecialchars($course['requisites']); ?>" placeholder="Requisites" required>
        <button type="submit" name="update_course">Update Course</button>
    </form>
</body>
</html>