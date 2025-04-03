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

// Debugging admin_id
$adminId = $_SESSION['admin_id'] ?? 0;
error_log("Admin ID: " . ($adminId ?: 'Not Set'));

if ($adminId === 0 && isset($_SESSION['email'])) {
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->bind_param("s", $_SESSION['email']);
    $stmt->execute();
    $stmt->bind_result($adminId);
    if ($stmt->fetch()) {
        $_SESSION['admin_id'] = $adminId; // Store it in the session
    }
    $stmt->close();
    error_log("Admin ID retrieved from database: " . $adminId);
}

$stmt = $conn->prepare("UPDATE document_requests SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("si", $status, $id);
if ($stmt->execute()) {
    // Log the status update
    if (insertLog($adminId, 'Update Status', "Updated document request ID $id to status $status", 'info')) {
        error_log("Log entry created for status update.");
    } else {
        error_log("Failed to create log entry for status update.");
    }

    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} else {
    // Log the failure
    if (insertLog($adminId, 'Update Status Failed', "Failed to update document request ID $id to status $status", 'error')) {
        error_log("Log entry created for failed status update.");
    } else {
        error_log("Failed to create log entry for failed status update.");
    }

    echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
}

$stmt->close();
$conn->close();
?>
