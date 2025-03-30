<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$id = $_POST['id'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($id) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'ID and status are required.']);
    exit();
}

$stmt = $conn->prepare("UPDATE document_requests SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("si", $status, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

$stmt->close();
$conn->close();
?>
