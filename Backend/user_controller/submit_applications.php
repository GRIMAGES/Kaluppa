<?php
require_once '../../Backend/connection.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file
ini_set("log_errors", 1);
ini_set("error_log", "../../Backend/logs/application_form_errors.log");

if (!isset($_SESSION['email'])) {
    error_log("User not logged in. Session email is not set.");
    header("Location: /Frontend/index.php");
    exit();
}

$userQuery = "SELECT id, first_name, middle_name, last_name, email FROM user WHERE email = ?";
$stmt = $conn->prepare($userQuery);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit();
}
$stmt->bind_param('s', $_SESSION['email']);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

if (!$user) {
    error_log("User not found with email: " . $_SESSION['email']);
    echo json_encode(['success' => false, 'error_code' => 2, 'message' => 'User not found']);
    exit();
}

$user_id = $user['id'];
$first_name = $user['first_name'];
$middle_name = $user['middle_name'];
$last_name = $user['last_name'];
$email = $user['email'];
$phone = $_POST['phone']; // Get phone number from form
$house_number = $_POST['house_number'];
$street = $_POST['street'];
$barangay = $_POST['barangay'];
$district = $_POST['district'];
$city = $_POST['city'];
$region = $_POST['region'];
$postal_code = $_POST['postal_code'];

$uploadDir = realpath(__DIR__ . '/../../Backend/Documents/Volunteer/');
error_log("Resolved path: " . var_export($uploadDir, true));
if ($uploadDir === false) {
    error_log("Invalid base directory path.");
    echo json_encode(['success' => false, 'message' => 'Invalid base directory path.']);
    exit();
}
$uploadFile = $uploadDir . DIRECTORY_SEPARATOR . basename($_FILES['resume']['name']);

if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        error_log("Failed to create directory: " . $uploadDir);
        echo json_encode(['success' => false, 'message' => 'Failed to create directory.']);
        exit();
    }
}

if (move_uploaded_file($_FILES['resume']['tmp_name'], $uploadFile)) {
    error_log("File uploaded successfully to: " . $uploadFile);
} else {
    error_log("File upload error: " . $_FILES['resume']['error']);
    echo json_encode(['success' => false, 'message' => 'File upload error.']);
    exit();
}

// Fetch 'work_id' from POST or set a default value
$work_id = $_POST['work_id'] ?? 0;

// Generate custom application ID (VOL-00001 format)
$query = "SELECT id FROM volunteer_application ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);
$row = $result->fetch_assoc();

if ($row) {
    $lastId = $row['id'];
    $num = (int)substr($lastId, 4); // Extract numeric part
    $newId = 'VOL-' . str_pad($num + 1, 5, '0', STR_PAD_LEFT);
} else {
    $newId = 'VOL-00001'; // First entry
}

error_log("Generated new ID: " . $newId); // Debugging statement

// Insert data into the 'volunteer_application' table
$stmt = $conn->prepare("INSERT INTO volunteer_application (id, work_id, user_id, first_name, middle_name, last_name, email, phone, house_number, street, barangay, district, city, region, postal_code, resume_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit();
}
$stmt->bind_param('siisssssssssssss', $newId, $work_id, $user_id, $first_name, $middle_name, $last_name, $email, $phone, $house_number, $street, $barangay, $district, $city, $region, $postal_code, $uploadFile);

if ($stmt->execute()) {
    error_log("Application submitted successfully for user ID: " . $user_id);
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully.']);
} else {
    error_log("Error submitting application: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to submit application.']);
}

// Log the query for debugging
error_log("Executed query: " . $stmt->error);

$stmt->close();
$conn->close();
?>
