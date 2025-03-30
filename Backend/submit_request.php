<?php
require_once 'connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$fullName = $_POST['fullName'] ?? '';
$email = $_POST['email'] ?? '';
$documentType = $_POST['documentType'] ?? '';
$reason = $_POST['reason'] ?? '';

if (empty($fullName) || empty($email) || empty($documentType) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO document_requests (full_name, email, document_type, reason) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit();
}

$stmt->bind_param("ssss", $fullName, $email, $documentType, $reason);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit request.']);
}

$stmt->close();
$conn->close();
?>
