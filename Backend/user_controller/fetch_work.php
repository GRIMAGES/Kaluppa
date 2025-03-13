<?php
require_once 'connection.php'; // Adjust the path to your database connection file

// Check if a work ID is provided
if (isset($_GET['work_id'])) {
    $workId = intval($_GET['work_id']); // Convert to integer to prevent SQL injection

    // Fetch details of the work with the given ID
    $query = "SELECT * FROM works WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $workId); // Bind the work ID as a parameter
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $work = mysqli_fetch_assoc($result);

            // Return work details as JSON
            echo json_encode([
                'id' => $work['id'],
                'title' => $work['title'],
                'description' => $work['description'],
                'image_path' => '../images/' . $work['image_path'], // Adjust the path as necessary
            ]);
        } else {
            echo json_encode(['error' => 'Work not found']);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['error' => 'Database query failed']);
    }
} else {
    echo json_encode(['error' => 'Invalid work ID']);
}

mysqli_close($conn);
?>
