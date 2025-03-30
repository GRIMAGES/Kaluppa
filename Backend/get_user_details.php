<?php
require_once 'connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email FROM user WHERE email = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $fullName = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];
    echo json_encode(['success' => true, 'full_name' => $fullName, 'email' => $user['email']]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
}
$stmt->close();
$conn->close();
?>
