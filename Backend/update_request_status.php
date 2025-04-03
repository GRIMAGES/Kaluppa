<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'connection.php';
require_once 'log_helper.php'; // Include log_helper.php

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

$adminId = $_SESSION['admin_id'] ?? 0; // Replace with actual admin ID from session
if ($adminId === 0 && isset($_SESSION['email'])) {
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->bind_param("s", $_SESSION['email']);
    $stmt->execute();
    $stmt->bind_result($adminId);
    if ($stmt->fetch()) {
        $_SESSION['admin_id'] = $adminId; // Store it in the session
    }
    $stmt->close();
}

$stmt = $conn->prepare("UPDATE document_requests SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("si", $status, $id);
if ($stmt->execute()) {
    // Log the status update
    insertLog($adminId, 'Update Status', "Updated document request ID $id to status $status", 'info');

    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} else {
    // Log the failure
    insertLog($adminId, 'Update Status Failed', "Failed to update document request ID $id to status $status", 'error');

    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

$stmt->close();
$conn->close();
?>
