<?php
require_once '../connection.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => true, 'message' => 'Announcement ID is required']);
    exit();
}

$id = intval($_GET['id']);

$query = "SELECT * FROM announcements WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $announcement = $result->fetch_assoc();

    // Ensure the image path is correctly resolved
    if (!empty($announcement['image'])) {
        $imagePath = $announcement['image'];
        // Avoid duplicating 'Frontend/uploads/' if it already exists
        if (!str_contains($imagePath, "Frontend/uploads/")) {
            $imagePath = "Frontend/uploads/" . $imagePath;
        }
        $announcement['image'] = $imagePath;
    } else {
        $announcement['image'] = ''; // No image available
    }

    echo json_encode([
        'error' => false,
        'title' => $announcement['title'],
        'content' => $announcement['content'],
        'image' => $announcement['image']
    ]);
} else {
    echo json_encode(['error' => true, 'message' => 'Announcement not found']);
}
?>
