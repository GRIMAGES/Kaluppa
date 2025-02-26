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
    $announcement['image'] = !empty($announcement['image']) ? "Frontend/uploads/" . $announcement['image'] : '';

    echo json_encode(['error' => false, 'title' => $announcement['title'], 'content' => $announcement['content'], 'image' => $announcement['image']]);
} else {
    echo json_encode(['error' => true, 'message' => 'Announcement not found']);
}
?>
