<?php
require_once '../../Backend/connection.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/multiuserlogin.php");
    exit();
}

$userQuery = "SELECT id, first_name, middle_name, last_name, email FROM user WHERE email = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param('s', $_SESSION['email']);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error_code' => 2, 'message' => 'User not found']);
    error_log("User not found with email: " . $_SESSION['email']);
    exit();
}

$user_id = $user['id'];
$full_name = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'];
$email = $user['email'];
$phone = $_POST['phone']; // Assuming phone is sent via POST request

$uploadDir = '/../Documents/Volunteer/';
$uploadFile = $uploadDir . basename($_FILES['resume']['name']);

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
}

if (move_uploaded_file($_FILES['resume']['tmp_name'], $uploadFile)) {
    echo "File uploaded successfully.";
} else {
    echo "File upload error.";
}

// Fetch 'work_id' from POST or set a default value
$work_id = $_POST['work_id'] ?? 0;

// Insert data into the 'applications' table
$stmt = $conn->prepare("INSERT INTO volunteer_application (work_id, user_id, name, email, phone, resume_path) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('iissss', $work_id, $user_id, $full_name, $email, $phone, $uploadFile);

if ($stmt->execute()) {
    echo "Application submitted successfully.";
} else {
    echo "Failed to submit application.";
    error_log("Error submitting application: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
